const puppeteer = require('puppeteer');

(async () => {

	const browser = await puppeteer.launch({
		headless: true,
		args: [
			// '--no-sandbox',
			'--disable-gpu',
			'--hide-scrollbars',
			// '--disable-setuid-sandbox',
		],
	});

	const page = await browser.newPage();
	await page.setDefaultNavigationTimeout(0);
	await page.setDefaultTimeout(0);
	await page.setViewport({
		width: 794,
		height: 1122,
	});

	await page.goto('https://www.minion.cz');
	await page.emulateMedia('screen');
	await page.pdf({
		path: 'sandbox.pdf',
		format: 'A4',
		margin: 0,
		printBackground: true,
	});
	await browser.close();
})();
