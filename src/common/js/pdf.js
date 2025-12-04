const puppeteer = require('puppeteer');
const fs = require('fs');

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
        const browser = await puppeteer.launch({
            executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox'] // Necesario para linux/servidores
        });

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