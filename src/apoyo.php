<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$plataformas = [
    [
        'titulo' => 'Contable histórico',
        'descripcion' => 'Versión anterior del software contable, disponible solo para consulta.',
        'icono' => 'fa-solid fa-tablet-screen-button',
        'tipo' => 'badge',
        'estado' => 'Solo consulta',
        'url' => '#',
        'target' => '_self',
    ],
    [
        'titulo' => 'Cronhis Financiero (Ambiente pruebas)',
        'descripcion' => 'Ambiente de pruebas del sistema financiero Cronhis, para ensayos y capacitación.',
        'icono' => 'fa-solid fa-flask',
        'tipo' => 'badge',
        'estado' => 'Ambiente de pruebas',
        'url' => '#',
        'target' => '_blank',
    ],
    [
        'titulo' => 'Pagina Institucional',
        'descripcion' => 'Sitio web oficial de RedSalud Casanare E.S.E.',
        'icono' => 'fa-solid fa-globe',
        'tipo' => 'link',
        'estado' => 'Ingresar',
        'url' => 'https://www.redsaludcasanare.gov.co',
        'target' => '_blank',
    ],
    [
        'titulo' => 'Manual de usuario',
        'descripcion' => 'Guía de uso y documentación de apoyo del sistema para los usuarios.',
        'icono' => 'fa-solid fa-book-open',
        'tipo' => 'link',
        'estado' => 'Ingresar',
        'url' => 'https://docs.google.com/document/d/1N8Z5_b0s8zrV3VwxZUiyqpfNBXWuJPKv4btfjsOW_EY/edit?tab=t.8ik1q1ivlfk',
        'target' => '_blank',
    ],
];

$cardsHtml = '';
foreach ($plataformas as $item) {
    $titulo = htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8');
    $descripcion = htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8');
    $icono = htmlspecialchars($item['icono'], ENT_QUOTES, 'UTF-8');
    $estado = htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8');
    $url = trim($item['url']) !== '' ? trim($item['url']) : '#';
    $href = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $target = ($item['target'] ?? '_blank') === '_self' ? '_self' : '_blank';
    $targetAttr = $target === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : 'target="_self"';

    if ($item['tipo'] === 'link') {
        $footerHtml = <<<HTML
            <span class="apoyo-link">
                <span>{$estado}</span>
                <i class="fa-solid fa-arrow-right"></i>
            </span>
HTML;
    } else {
        $footerHtml = <<<HTML
            <span class="apoyo-badge">
                <i class="fa-regular fa-clock"></i>
                <span>{$estado}</span>
            </span>
HTML;
    }

    $cardsHtml .= <<<HTML
        <a href="{$href}" {$targetAttr} class="apoyo-card">
            <div>
                <div class="apoyo-icon">
                    <i class="{$icono}"></i>
                </div>
                <h2 class="apoyo-card-title">{$titulo}</h2>
                <p class="apoyo-card-desc">{$descripcion}</p>
            </div>
            <div class="apoyo-card-footer">
                {$footerHtml}
            </div>
        </a>
HTML;
}

$customCss = <<<CSS
.apoyo-section {
    width: 100%;
    min-height: calc(100vh - 60px);
    background-color: #f3f7fb;
    padding: 3rem 1.5rem;
}

.apoyo-container {
    max-width: 1180px;
    margin: 0 auto;
}

.apoyo-eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #1a659d;
    margin-bottom: 0.4rem;
}

.apoyo-title {
    font-size: 1.9rem;
    font-weight: 700;
    color: #0f2537;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.apoyo-subtitle {
    font-size: 0.95rem;
    color: #64748b;
    margin-bottom: 2.25rem;
}

.apoyo-carousel {
    position: relative;
}

.apoyo-track {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    scroll-snap-type: x mandatory;
    padding: 0.5rem 0.25rem 1rem;
    scrollbar-width: none;
}

.apoyo-track::-webkit-scrollbar {
    display: none;
}

.apoyo-card {
    flex: 0 0 calc((100% - 3rem) / 3);
    scroll-snap-align: start;
    background-color: #ffffff;
    border: 1px solid #e6edf5;
    border-radius: 14px;
    padding: 1.75rem;
    min-height: 235px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(15, 37, 55, 0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.apoyo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(15, 37, 55, 0.11);
    color: inherit;
}

.apoyo-card:focus-visible {
    outline: 2px solid #1a659d;
    outline-offset: 2px;
}

.apoyo-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background-color: #eef5fb;
    color: #1a659d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    margin-bottom: 1.15rem;
}

.apoyo-card-title {
    font-size: 1.08rem;
    font-weight: 700;
    color: #0f2537;
    margin-bottom: 0.55rem;
    line-height: 1.35;
}

.apoyo-card-desc {
    font-size: 0.9rem;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 1.4rem;
}

.apoyo-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background-color: #fef3dc;
    color: #9a6715;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
}

.apoyo-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: #1a659d;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: gap 0.2s ease, color 0.2s ease;
}

.apoyo-card:hover .apoyo-link,
.apoyo-link:hover {
    color: #134b75;
    gap: 0.7rem;
}

.apoyo-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background-color: rgba(71, 85, 105, 0.85);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
    z-index: 5;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.apoyo-nav-btn:hover {
    background-color: #1a659d;
    transform: translateY(-50%) scale(1.06);
}

.apoyo-nav-prev {
    left: -18px;
}

.apoyo-nav-next {
    right: -18px;
}

@media (max-width: 991.98px) {
    .apoyo-card {
        flex: 0 0 calc((100% - 1.5rem) / 2);
    }
}

@media (max-width: 767.98px) {
    .apoyo-section {
        padding: 2rem 1rem;
    }

    .apoyo-title {
        font-size: 1.5rem;
    }

    .apoyo-card {
        flex: 0 0 100%;
    }

    .apoyo-nav-prev {
        left: 6px;
    }

    .apoyo-nav-next {
        right: 6px;
    }
}
CSS;

$customJs = <<<JS
document.addEventListener("DOMContentLoaded", function () {
    const track = document.getElementById("apoyoTrack");
    const prevBtn = document.getElementById("apoyoPrev");
    const nextBtn = document.getElementById("apoyoNext");

    if (!track || !prevBtn || !nextBtn) {
        return;
    }

    const scrollStep = () => {
        const card = track.querySelector(".apoyo-card");
        if (!card) return 320;
        const styles = window.getComputedStyle(track);
        const gap = parseFloat(styles.columnGap || styles.gap || "24");
        return card.getBoundingClientRect().width + gap;
    };

    prevBtn.addEventListener("click", function () {
        track.scrollBy({ left: -scrollStep(), behavior: "smooth" });
    });

    nextBtn.addEventListener("click", function () {
        track.scrollBy({ left: scrollStep(), behavior: "smooth" });
    });
});
JS;

$content = <<<HTML
<section class="apoyo-section">
    <div class="apoyo-container">
        <div class="apoyo-eyebrow">OTRAS PLATAFORMAS</div>
        <h1 class="apoyo-title">Servicios de apoyo institucional</h1>
        <p class="apoyo-subtitle">Plataformas complementarias de consulta, pruebas, formación, gestión documental y soporte.</p>

        <div class="apoyo-carousel">
            <button type="button" class="apoyo-nav-btn apoyo-nav-prev" id="apoyoPrev" aria-label="Anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="apoyo-track" id="apoyoTrack">
                {$cardsHtml}
            </div>

            <button type="button" class="apoyo-nav-btn apoyo-nav-next" id="apoyoNext" aria-label="Siguiente">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssInline($customCss);
$plantilla->addScriptInline($customJs);
echo $plantilla->render();
