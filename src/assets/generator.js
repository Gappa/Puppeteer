'use strict';

class Generator
{
	constructor(args)
	{
		this.args = args;
		this.outputPdf = false;
		this.outputImage = false;
		this.browserArgs = [
			'--disable-gpu',
			'--hide-scrollbars',
		];

		// console.log(args);
		// return;

		this.checkArg(this.args.input);
		this.checkArg(this.args.inputMode);
		this.checkArg(this.args.output);

		this.httpAuth = this.getHttpAuth();
		this.viewport = this.getViewport();

		// Process outputMode, this is not either/or, it can be both
		if (this.isset(this.args.pdf)) {
			this.outputPdf = true;
			this.pageParameters = {
				path: this.args.output + '.pdf',
				margin: 0,
				printBackground: true,
				// scale: 0.9,
				// preferCSSPageSize: true,
			};
			this.setPageFormat();
			this.setPageDimensions();
			this.setLandscape();
		}

		if (this.isset(this.args.image)) {
			this.outputImage = true;
		}

		if (this.isset(this.args.sandbox) && this.args.sandbox === false) {
			this.browserArgs.push('--no-sandbox');
		}
	}


	isset(variable)
	{
		return typeof variable !== 'undefined';
	}


	checkArg(arg, message = '')
	{
		if (!this.isset(arg)) {
			throw Error('Argument ' + arg + ' not set. ' + message);
		}
	}


	getHttpAuth()
	{
		if (this.isset(this.args.httpUser) && this.isset(this.args.httpPass)) {
			return {
				username: this.args.httpUser,
				password: this.args.httpPass,
			};
		}
	}


	getViewport()
	{
		if (this.isset(this.args.viewportWidth) && this.isset(this.args.viewportHeight)) {
			return {
				width: this.args.viewportWidth,
				height: this.args.viewportHeight,
			};
		}
	}


	setPageFormat()
	{
		if (this.isset(this.args.pageFormat)) {
			this.pageParameters['format'] = this.args.pageFormat;
		}
	}


	setPageDimensions()
	{
		if (
			this.isset(this.args.pageWidth)
			&&
			this.isset(this.args.pageHeight)
		) {
			this.pageParameters['width'] = this.args.pageWidth + 'mm';
			this.pageParameters['height'] = this.args.pageHeight + 'mm';
		}
	}


	setLandscape()
	{
		if (this.isset(this.args.landscape)) {
			this.pageParameters['landscape'] = true;
		}
	}


	async generate()
	{
		const browser = await puppeteer.launch({
			headless: true,
			args: this.browserArgs,
		});

		const page = await browser.newPage();

		if (this.httpAuth) {
			// console.log(this.httpAuth);

			// await page.authenticate(this.httpAuth); // this for some reason doesn't work

			const auth = new Buffer(`${this.httpAuth.username}:${this.httpAuth.password}`).toString('base64');
			await page.setExtraHTTPHeaders({
				'Authorization': `Basic ${auth}`
			});
			// page.on('request', request => console.log(`Request: ${request.resourceType}: ${request.url} (${JSON.stringify(request.headers)})`));
		}

		await page.setDefaultNavigationTimeout(0);
		await page.setDefaultTimeout(0);

		if (this.viewport) {
			await page.setViewport(this.viewport);
		}


		// Set url/content
		switch (this.args.inputMode) {
			case 'file':
				await page.setContent(fs.readFileSync(this.args.input, 'utf8'));
				break;

			case 'url':
				await page.goto(this.args.input);
				break;
		}

		if (this.outputImage) {
			await page.screenshot({
				path: this.args.output + '.png',
				fullPage: true,
				// type: 'png', // default
				// encoding: 'binary', // default
			});
		}

		if (this.outputPdf) {
			await page.emulateMediaType('screen');
			await page.pdf(this.pageParameters);
		}

		await browser.close();
	}

}

const puppeteer = require('puppeteer');
const argv = require('minimist')(process.argv.slice(2));
const fs = require('fs');
const generator = new Generator(argv);

generator.generate().catch((e) => {
	console.log(e);
});

