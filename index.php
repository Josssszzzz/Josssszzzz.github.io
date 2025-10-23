<?php

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo de Pautado</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles/calendar.css">
    <link rel="stylesheet" href="styles/darktheme.css">
    <link rel="stylesheet" href="styles/root.css">
    <link rel="stylesheet" href="styles/animations.css"> 

    <!-- Estilos para las miniaturas -->
    <style>
        .modal-thumbnail {
            width: 80px; height: 45px; object-fit: cover;
            border-radius: 4px; cursor: pointer; border: 1px solid #dee2e6;
            transition: transform 0.2s ease-in-out;
            margin-right: 8px;
        }
        .modal-thumbnail:hover { transform: scale(1.1); }

        .thumbnail-container {
            position: relative;
            display: inline-block;
        }
        .play-overlay-icon {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            color: white;
            opacity: 0.8;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.7);
            pointer-events: none; 
        }
    </style>
</head>
<body>


<div id="exit-btn">
    <a id="logoutLink" href="logout.php" class="btn btn-outline-danger">
        <i class="bi bi-box-arrow-right me-1"></i>Salir
    </a>
</div>


<div id="theme-toggle">
    <i class="bi bi-sun-fill"></i>
    <i class="bi bi-moon-stars-fill"></i>
</div>


<div class="container-fluid my-4 px-md-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <a class="navbar-brand" href="#">
            <img src="https://tvctepa.com/tvctepa/access/assets/images/logo.webp" alt="Logo TVCable Tepa" height="100">
        </a>
        <div class="d-flex align-items-center">
            <span class="d-none d-md-inline me-3"><i class="bi bi-person-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </header>


    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white p-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center">
                <label for="canalSelector" class="fw-bold me-2">CANAL:</label>
                <select class="form-select w-auto" id="canalSelector"></select>
            </div>
                <div class="d-flex gap-2">
                    <a href="informes.php" class="btn btn-primary">
                        <i class="bi bi-bar-chart-line-fill me-2"></i>Estadísticas y Reportes
                    </a>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['informatica', 'Publicidad', 'monitor'])): ?>
                <a href="gestion_pautas.php" class="btn btn-info">
                    <i class="bi bi-clock-history"></i> Programación de Pautas
                </a>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'informatica'): ?>
                <a href="gestion_usuarios.php" class="btn btn-secondary">
                    <i class="bi bi-people-fill me-2"></i>Administrar Usuarios
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['username']) && in_array($_SESSION['username'], ['Eduardo', 'Mario'])): ?>
                <a href="gestion_canales.php" class="btn btn-success">
                    <i class="bi bi-camera-reels"></i> Administrar Canales
                </a>    
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['informatica', 'Publicidad', 'monitor'])): ?>
                <a href="gestion_spots.php" class="btn btn-warning">
                    <i class="bi bi-badge-ad-fill"></i> Biblioteca de Anuncios
                </a>
            <?php endif; ?>
            </div>
        </div>
        <nav class="calendar-nav d-flex align-items-center justify-content-center p-2">
            <button id="prevMonth" class="btn btn-outline-secondary border-0"><i class="bi bi-chevron-left fs-5"></i></button>
            <h4 id="currentMonthYear" class="fw-bold text-center mx-3 my-0" style="width: 200px;"></h4>
            <button id="nextMonth" class="btn btn-outline-secondary border-0"><i class="bi bi-chevron-right fs-5"></i></button>
        </nav>
        <div class="card-body">
            <div id="loadingMessage" class="text-center p-5" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
            </div>
            <div class="table-responsive">
                <table id="pautasTable" class="table">
                    <thead id="tableHeader"></thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Modal Descargas -->
<div class="modal fade" id="descargasModal" tabindex="-1" aria-labelledby="descargasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descargasModalLabel">Archivos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul id="descargasList" class="list-group list-group-flush"></ul>
            </div>
            <div class="modal-footer">
                <button id="btnDescargarTodos" type="button" class="btn btn-success">Descargar Todos</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para el Reproductor de Video -->
<div class="modal fade" id="videoPlayerModal" tabindex="-1" aria-labelledby="videoPlayerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoPlayerModalLabel">Reproduciendo...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <video id="videoPlayer" width="100%" controls controlsList="nodownload" autoplay>
                    Tu navegador no soporta el elemento de video.
                </video>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const selector = document.getElementById('canalSelector');
        const tableHeader = document.getElementById('tableHeader');
        const tableBody = document.getElementById('tableBody');
        const loadingMessage = document.getElementById('loadingMessage');
        const pautasTable = document.getElementById('pautasTable');
        const currentMonthYearEl = document.getElementById('currentMonthYear');
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        
        const timbresMap = new Map();
        let ultimoCanal = null;
        let currentDate = new Date();

        const logoutLink = document.querySelector('#exit-btn a[href="logout.php"]');
        if (logoutLink) {
            logoutLink.addEventListener('click', async (e) => {
                e.preventDefault();
                const result = await Swal.fire({
                    title: '¿Seguro que quieres salir?', text: 'Se cerrará tu sesión actual.', icon: 'question',
                    showCancelButton: true, confirmButtonText: 'Sí, salir', cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#198754', cancelButtonColor: '#d33'
                });
                if (result.isConfirmed) { window.location.href = logoutLink.href; }
            });
        }
        
        async function cargarCanales() {
            try {
                const response = await fetch('api.php?action=get_channels');
                if (!response.ok) { throw new Error('No se pudo obtener la lista de canales.'); }
                const canales = await response.json();
                selector.innerHTML = '';
                canales.forEach(canal => {
                    const option = document.createElement('option');
                    option.value = canal;
                    option.textContent = canal;
                    selector.appendChild(option);
                });
            } catch (error) {
                console.error('Error al cargar canales:', error);
                selector.innerHTML = '<option>Error al cargar</option>';
            }
        }

        async function cargarCalendario(canal, year, month) {
            loadingMessage.style.display = 'block';
            pautasTable.style.display = 'none';
            timbresMap.clear();
            ultimoCanal = canal;

            try {
                const url = `api.php?canal=${encodeURIComponent(canal)}&year=${year}&month=${month}`;
                const response = await fetch(url);
                if (!response.ok) {
                    if(response.status === 403) window.location.href = 'login.php';
                    throw new Error('Respuesta de red no válida.');
                }
                const data = await response.json();
                
                const monthName = data.monthName.charAt(0).toUpperCase() + data.monthName.slice(1);
                currentMonthYearEl.textContent = `${monthName} ${data.year}`;
                tableHeader.innerHTML = '';
                tableBody.innerHTML = '';
                const headerRow = document.createElement('tr');
                data.diasSemana.forEach(dia => {
                    headerRow.innerHTML += `<th class="text-muted fw-normal text-center">${dia}</th>`;
                });
                tableHeader.appendChild(headerRow);
                let calendarCells = [];
                for (let i = 0; i < data.startDayOffset; i++) { calendarCells.push('<td></td>'); }

                const today = new Date();
                const isCurrentMonth = today.getFullYear() === year && (today.getMonth() + 1) === month;

                for (let dayCounter = 1; dayCounter <= data.daysInMonth; dayCounter++) {
                    const cellClass = (isCurrentMonth && dayCounter === today.getDate()) ? 'current-day' : '';
                    let dayContentHTML = `<div class="day-content">`;

                    // --- LÓGICA CORREGIDA PARA MOSTRAR SIEMPRE EL BOTÓN ---
                    if (data.timbres && data.timbres[dayCounter] && data.timbres[dayCounter].length > 0) {
                        const pautasDelDia = data.timbres[dayCounter];
                        const key = `${year}-${month}-${dayCounter}`;
                        timbresMap.set(key, pautasDelDia);

                        // Muestra siempre el botón, sin importar si es 1 o más pautas.
                        const timbresCount = pautasDelDia.length;
                        dayContentHTML += `<button class="btn btn-outline-primary btn-sm w-100 btn-download" type="button"
                            data-bs-toggle="modal" data-bs-target="#descargasModal"
                            data-date="${key}" data-fecha-legible="${dayCounter} ${monthName} ${data.year}">
                            <i class="bi bi-film me-1"></i> Pautas (${timbresCount})
                        </button>`;
                    }
                    
                    dayContentHTML += `</div>`;
                    calendarCells.push(`<td class="${cellClass}">
                        <div class="day-number">${dayCounter}</div>
                        ${dayContentHTML}
                    </td>`);
                }

                let calendarHTML = '<tr>';
                calendarCells.forEach((cell, index) => {
                    calendarHTML += cell;
                    if ((index + 1) % 7 === 0 && index + 1 < calendarCells.length) {
                        calendarHTML += '</tr><tr>';
                    }
                });
                while (calendarCells.length % 7 !== 0) { calendarCells.push('<td></td>'); calendarHTML += '<td></td>'; }
                calendarHTML += '</tr>';
                tableBody.innerHTML = calendarHTML;
                
            } catch (error) {
                console.error('Error al cargar el calendario:', error);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">Error al cargar los datos. Por favor, recarga la página.</td></tr>`;
            } finally {
                loadingMessage.style.display = 'none';
                pautasTable.style.display = 'table';
            }
        }

        function changeMonth(offset) {
            currentDate.setMonth(currentDate.getMonth() + offset);
            cargarCalendario(selector.value, currentDate.getFullYear(), currentDate.getMonth() + 1);
        }

        prevMonthBtn.addEventListener('click', () => changeMonth(-1));
        nextMonthBtn.addEventListener('click', () => changeMonth(1));
        selector.addEventListener('change', () => changeMonth(0));

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-download');
            if (!btn) return;

            const key = btn.dataset.date;
            const fechaLegible = btn.dataset.fechaLegible || '';
            const pautas = timbresMap.get(key) || [];
            document.getElementById('descargasModalLabel').textContent = `Archivos — ${fechaLegible}`;
            const list = document.getElementById('descargasList');
            list.innerHTML = '';

            if (pautas.length === 0) {
                list.innerHTML = `<li class="list-group-item text-muted">No hay archivos para esta fecha.</li>`;
                return;
            }
            
            pautas.forEach(pauta => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                const downloadUrl = `download.php?file=${encodeURIComponent(pauta.filepath)}&canal=${encodeURIComponent(ultimoCanal)}`;
                const playUrl = `download.php?file=${encodeURIComponent(pauta.filepath)}&canal=${encodeURIComponent(ultimoCanal)}&play=1`;

                const dateString = pauta.filepath.split('/')[0];
                const timeString = pauta.filename.substring(0, 5).replace(/-/g, ':');
                const dateObj = new Date(`${dateString}T${timeString}:00`);
                const dayName = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(dateObj);
                const dayNumber = new Intl.DateTimeFormat('es-ES', { day: '2-digit' }).format(dateObj);
                const formattedDate = (dayName.charAt(0).toUpperCase() + dayName.slice(1)) + ` ${dayNumber}, ${timeString}`;

                let playElement = '';
                if (pauta.thumbnail) {
                    playElement = `
                        <div class="thumbnail-container">
                            <img src="${pauta.thumbnail}" class="modal-thumbnail btn-play-video" alt="Miniatura" title="Reproducir video" data-video-src="${playUrl}" data-video-title="${pauta.filename}">
                            <i class="bi bi-play-circle-fill play-overlay-icon"></i>
                        </div>`;
                } else {
                    playElement = `<button type="button" class="btn btn-link text-body p-1 mx-1 btn-play-video" 
                                           data-video-src="${playUrl}" data-video-title="${pauta.filename}" title="Reproducir video">
                                       <i class="bi bi-play-circle fs-5 text-success"></i>
                                   </button>`;
                }

                li.innerHTML = `
                    <span class="text-truncate" title="${pauta.filename}">${formattedDate}</span>
                    <div class="d-flex align-items-center">
                        ${playElement}
                        <a href="${downloadUrl}" class="btn btn-link text-body p-1" title="Descargar video">
                            <i class="bi bi-download fs-5 text-primary"></i>
                        </a>
                    </div>`;
                list.appendChild(li);
            });
            document.getElementById('btnDescargarTodos').dataset.key = key;
        });
        
        document.getElementById('btnDescargarTodos').addEventListener('click', () => {
            const key = document.getElementById('btnDescargarTodos').dataset.key;
            if (!key) return;
            const pautas = timbresMap.get(key) || [];
            if (pautas.length > 0) {
                const filepaths = pautas.map(p => p.filepath);
                const url = `zip_download.php?canal=${encodeURIComponent(ultimoCanal)}&files=${encodeURIComponent(filepaths.join(','))}`;
                window.location.href = url;
            }
        });

        document.addEventListener('click', (e) => {
            const playBtn = e.target.closest('.btn-play-video');
            if (!playBtn) return;

            const videoSrc = playBtn.dataset.videoSrc;
            const videoTitle = playBtn.dataset.videoTitle;
            const videoPlayerModalEl = document.getElementById('videoPlayerModal');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoPlayerLabel = document.getElementById('videoPlayerModalLabel');

            videoPlayerLabel.textContent = videoTitle;
            videoPlayer.src = videoSrc;
            
            const modalInstance = new bootstrap.Modal(videoPlayerModalEl);
            modalInstance.show();
        });

        const videoPlayerModalEl = document.getElementById('videoPlayerModal');
        videoPlayerModalEl.addEventListener('hidden.bs.modal', () => {
            const videoPlayer = document.getElementById('videoPlayer');
            videoPlayer.pause();
            videoPlayer.src = '';
        });

        await cargarCanales();
        changeMonth(0);
        
        setInterval(() => {
            if (!document.body.classList.contains('modal-open')) {
                cargarCalendario(selector.value, currentDate.getFullYear(), currentDate.getMonth() + 1);
            }
        }, 30000);

    });

    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    const toggleTheme = () => {
        body.classList.toggle('dark-mode');
        localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
    };
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') { body.classList.add('dark-mode'); }
    themeToggle.addEventListener('click', toggleTheme);
</script>
</body>
</html>