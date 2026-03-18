const puppeteer = require('puppeteer');
const fs = require('fs');

function resolverRutaNavegador() {
    const rutasCandidatas = [
        process.env.PUPPETEER_EXECUTABLE_PATH,
        process.env.CHROME_PATH,
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser'
    ].filter(Boolean);

    for (const ruta of rutasCandidatas) {
        if (fs.existsSync(ruta)) {
            return ruta;
        }
    }

    return null;
}

(async () => {
    // 1. Obtener argumentos desde PHP (ruta del HTML temporal y ruta de salida)
    const args = process.argv.slice(2);
    const inputHtmlPath = args[0];
    const outputPdfPath = args[1];

    if (!inputHtmlPath || !outputPdfPath) {
        console.error("Faltan argumentos: node generar_pdf.js <input> <output>");
        process.exit(1);
    }

    try {
        // 2. Leer el contenido del archivo HTML temporal creado por PHP
        const htmlContent = fs.readFileSync(inputHtmlPath, 'utf8');

        // 3. Iniciar el navegador
        const launchOptions = {
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox'] // Necesario para linux/servidores
        };

        const executablePath = resolverRutaNavegador();
        if (executablePath) {
            launchOptions.executablePath = executablePath;
        }

        const browser = await puppeteer.launch(launchOptions);

        const page = await browser.newPage();

        // 4. Asignar el contenido HTML
        // waitUntil: 'networkidle0' espera a que carguen las imágenes/css
        await page.setContent(htmlContent, { waitUntil: 'networkidle0' });

        // 5. Generar el PDF
        await page.pdf({
            path: outputPdfPath,
            format: 'A4',
            printBackground: true, // Imprimir colores de fondo
            margin: { top: '10mm', right: '10mm', bottom: '10mm', left: '10mm' }
        });

        await browser.close();
        console.log("PDF Generado correctamente");

    } catch (error) {
        console.error("Error generando PDF:", error);
        process.exit(1);
    }
})();
