(function () {
    'use strict';

    const config = window.APP_CONFIG || {};
    const dataApi = config.dataApi || {};
    const requiredFields = ['baseUrl', 'apiKey', 'dataSource', 'database'];
    const campaignsCollection = dataApi.collection || (dataApi.collections ? dataApi.collections.campaigns : null) || 'campaigns';

    const missingConfig = requiredFields.filter((key) => !dataApi[key]);
    if (!campaignsCollection) {
        missingConfig.push('collection');
    }

    const campaignsBody = document.getElementById('campaignsBody');
    const campaignForm = document.getElementById('campaignForm');
    const toastEl = document.getElementById('appToast');
    const toastMessage = document.getElementById('toastMessage');
    const filterChannelInput = document.getElementById('filterChannel');
    const btnApplyFilter = document.getElementById('btnApplyFilter');
    const btnClearFilter = document.getElementById('btnClearFilter');

    let campaigns = [];

    function ensureConfig() {
        if (missingConfig.length === 0) {
            return true;
        }
        const message = `Falta configurar: ${missingConfig.join(', ')} en assets/config.js`;
        showToast(message, true);
        disableForm(message);
        renderEmpty(message);
        return false;
    }

    function disableForm(message) {
        if (!campaignForm) {
            return;
        }
        Array.from(campaignForm.elements).forEach((element) => {
            element.disabled = true;
        });
        const warning = document.createElement('div');
        warning.className = 'alert alert-warning mt-3';
        warning.textContent = message;
        campaignForm.parentElement?.appendChild(warning);
    }

    function renderEmpty(message) {
        if (!campaignsBody) {
            return;
        }
        campaignsBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">${escapeHtml(message)}</td></tr>`;
    }

    function getBaseUrl() {
        return String(dataApi.baseUrl || '').replace(/\/$/, '');
    }

    async function callDataApi(action, payload) {
        const url = `${getBaseUrl()}/action/${action}`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'api-key': dataApi.apiKey,
                'Access-Control-Request-Headers': '*',
            },
            body: JSON.stringify(Object.assign({
                dataSource: dataApi.dataSource,
                database: dataApi.database,
                collection: campaignsCollection,
            }, payload || {})),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errorMessage = data.error || data.message || 'Error al comunicar con MongoDB Atlas';
            throw new Error(errorMessage);
        }
        return data;
    }

    async function fetchCampaigns(filterText = '') {
        const filter = {};
        if (filterText) {
            filter.channel = { $regex: filterText, $options: 'i' };
        }
        const result = await callDataApi('find', {
            filter,
            sort: { scheduledAt: 1 },
        });
        campaigns = (result.documents || []).map(mapDocumentToCampaign);
        renderCampaigns();
    }

    function mapDocumentToCampaign(document) {
        const id = parseDocumentId(document?._id);
        return {
            id,
            title: document.title,
            channel: document.channel,
            scheduledAt: document.scheduledAt,
            durationSeconds: document.durationSeconds,
            assetUrl: document.assetUrl,
            notes: document.notes,
            status: document.status || 'programada',
        };
    }

    function parseDocumentId(rawId) {
        if (!rawId) {
            return undefined;
        }
        if (typeof rawId === 'string') {
            return rawId;
        }
        if (rawId.$oid) {
            return rawId.$oid;
        }
        return String(rawId);
    }

    function renderCampaigns() {
        if (!campaignsBody) {
            return;
        }
        campaignsBody.innerHTML = '';
        if (campaigns.length === 0) {
            campaignsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay pautas registradas todavía.</td></tr>';
            return;
        }

        const fragment = document.createDocumentFragment();
        campaigns.forEach((campaign) => {
            const tr = document.createElement('tr');
            const scheduledDate = formatDateTime(campaign.scheduledAt);
            const duration = campaign.durationSeconds ? `${campaign.durationSeconds}s` : '—';
            const statusClass = `status-${(campaign.status || 'programada').toLowerCase()}`;
            const statusLabel = campaign.status ? capitalize(campaign.status) : 'Programada';

            tr.innerHTML = `
                <td class="fw-semibold">${escapeHtml(campaign.channel || '')}</td>
                <td>
                    <div class="fw-semibold">${escapeHtml(campaign.title || '')}</div>
                    ${campaign.assetUrl ? `<a class="small" href="${encodeURI(campaign.assetUrl)}" target="_blank" rel="noopener">Ver recurso</a>` : ''}
                    ${campaign.notes ? `<div class="text-muted small">${escapeHtml(campaign.notes)}</div>` : ''}
                </td>
                <td>${scheduledDate}</td>
                <td>${duration}</td>
                <td><span class="status-pill ${statusClass}">${statusLabel}</span></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-success btn-complete" data-id="${campaign.id}" ${campaign.status === 'completada' ? 'disabled' : ''}>
                            <i class="bi bi-check2-circle"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-delete" data-id="${campaign.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            fragment.appendChild(tr);
        });
        campaignsBody.appendChild(fragment);
    }

    campaignForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!ensureConfig()) {
            return;
        }
        const formData = new FormData(campaignForm);
        const payload = Object.fromEntries(formData.entries());
        payload.durationSeconds = payload.durationSeconds ? Number(payload.durationSeconds) : undefined;
        payload.scheduledAt = payload.scheduledAt ? new Date(payload.scheduledAt).toISOString() : undefined;

        try {
            await callDataApi('insertOne', {
                document: Object.assign({
                    status: 'programada',
                    createdAt: new Date().toISOString(),
                }, cleanUndefined(payload)),
            });
            campaignForm.reset();
            filterChannelInput.value = '';
            showToast('Pauta registrada correctamente.');
            await fetchCampaigns();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    campaignsBody?.addEventListener('click', async (event) => {
        const target = event.target;
        const button = target.closest('button');
        if (!button) {
            return;
        }
        const id = button.dataset.id;
        if (!id) {
            return;
        }

        if (button.classList.contains('btn-delete')) {
            if (!confirm('¿Seguro que deseas eliminar esta pauta?')) {
                return;
            }
            await performDelete(id);
        }

        if (button.classList.contains('btn-complete')) {
            await performUpdate(id, { status: 'completada' });
        }
    });

    btnApplyFilter?.addEventListener('click', async () => {
        try {
            await fetchCampaigns(filterChannelInput.value.trim());
        } catch (error) {
            showToast(error.message, true);
        }
    });

    btnClearFilter?.addEventListener('click', async () => {
        filterChannelInput.value = '';
        try {
            await fetchCampaigns();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    async function performDelete(id) {
        try {
            await callDataApi('deleteOne', {
                filter: { _id: { $oid: id } },
            });
            showToast('Pauta eliminada.');
            await fetchCampaigns(filterChannelInput.value.trim());
        } catch (error) {
            showToast(error.message, true);
        }
    }

    async function performUpdate(id, changes) {
        try {
            await callDataApi('updateOne', {
                filter: { _id: { $oid: id } },
                update: { $set: cleanUndefined(Object.assign({}, changes, { updatedAt: new Date().toISOString() })) },
            });
            showToast('Pauta actualizada.');
            await fetchCampaigns(filterChannelInput.value.trim());
        } catch (error) {
            showToast(error.message, true);
        }
    }

    function cleanUndefined(obj) {
        return Object.fromEntries(Object.entries(obj).filter(([, value]) => value !== undefined && value !== ''));
    }

    function showToast(message, isError = false) {
        toastMessage.textContent = message;
        toastEl.classList.toggle('text-bg-danger', Boolean(isError));
        toastEl.classList.toggle('text-bg-primary', !isError);
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
        toast.show();
    }

    function formatDateTime(value) {
        if (!value) {
            return '—';
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString('es-MX', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    }

    function capitalize(text) {
        if (!text) {
            return '';
        }
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function escapeHtml(value) {
        value = value == null ? '' : String(value);
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!ensureConfig()) {
            return;
        }
        try {
            await fetchCampaigns();
        } catch (error) {
            showToast(error.message, true);
        }
    });
})();
