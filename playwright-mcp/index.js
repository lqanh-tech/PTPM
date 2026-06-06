#!/usr/bin/env node

/**
 * Playwright MCP Server for Pi Agent
 * Provides browser automation tools via Model Context Protocol
 *
 * Tools (16 total):
 *   Original: open_page, click, fill, get_content, screenshot, close_page
 *   New:      wait_for_selector, scroll, press_key, evaluate, get_links,
 *             get_images, select_option, go_back, go_forward, reload_page
 */

const { Server } = require("@modelcontextprotocol/sdk/server/index.js");
const {
	StdioServerTransport,
} = require("@modelcontextprotocol/sdk/server/stdio.js");
const {
	CallToolRequestSchema,
	ListToolsRequestSchema,
} = require("@modelcontextprotocol/sdk/types.js");

let playwright, browser, context, page;

async function initPlaywright() {
	if (!playwright) {
		playwright = require("playwright");
	}
	if (!browser) {
		browser = await playwright.chromium.launch({ headless: true });
		context = await browser.newContext();
	}
	if (!page) {
		page = await context.newPage();
	}
	return page;
}

function cleanText(html) {
	let text = html.replace(
		/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
		"",
	);
	text = text.replace(/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/gi, "");
	text = text.replace(/<[^>]+>/g, " ");
	text = text.replace(/\s+/g, " ").trim();
	return text;
}

// Helper: check if a page is open, throw if not
function requirePage() {
	if (!page) throw new Error("No page open. Call open_page first.");
	return page;
}

const server = new Server(
	{ name: "playwright-mcp", version: "2.0.0" },
	{ capabilities: { tools: {} } },
);

// --- Tool Definitions ---

server.setRequestHandler(ListToolsRequestSchema, async () => ({
	tools: [
		// -- Original 6 --
		{
			name: "open_page",
			description: "Open a URL in browser. Returns page title.",
			inputSchema: {
				type: "object",
				properties: { url: { type: "string", description: "URL to open" } },
				required: ["url"],
			},
		},
		{
			name: "click",
			description: "Click element by CSS selector",
			inputSchema: {
				type: "object",
				properties: {
					selector: { type: "string", description: "CSS selector" },
				},
				required: ["selector"],
			},
		},
		{
			name: "fill",
			description: "Fill text into input field",
			inputSchema: {
				type: "object",
				properties: {
					selector: { type: "string", description: "CSS selector" },
					text: { type: "string", description: "Text to fill" },
				},
				required: ["selector", "text"],
			},
		},
		{
			name: "get_content",
			description: "Get cleaned text content of current page",
			inputSchema: { type: "object", properties: {} },
		},
		{
			name: "screenshot",
			description: "Take screenshot, return base64 PNG",
			inputSchema: {
				type: "object",
				properties: {
					fullPage: {
						type: "boolean",
						description: "Full page (default false)",
					},
				},
			},
		},
		{
			name: "close_page",
			description: "Close browser and cleanup",
			inputSchema: { type: "object", properties: {} },
		},

		// -- New 10 --
		{
			name: "wait_for_selector",
			description:
				"Wait for an element matching the selector to appear in the DOM.",
			inputSchema: {
				type: "object",
				properties: {
					selector: { type: "string", description: "CSS selector to wait for" },
					timeout_ms: {
						type: "number",
						description: "Timeout in milliseconds (default 30000)",
					},
				},
				required: ["selector"],
			},
		},
		{
			name: "scroll",
			description: "Scroll the page up or down by a given number of pixels.",
			inputSchema: {
				type: "object",
				properties: {
					direction: {
						type: "string",
						enum: ["up", "down"],
						description: "Scroll direction",
					},
					pixels: {
						type: "number",
						description: "Pixels to scroll (default 500)",
					},
				},
			},
		},
		{
			name: "press_key",
			description: "Press a keyboard key (e.g. Enter, ArrowDown, Escape, Tab).",
			inputSchema: {
				type: "object",
				properties: {
					key: {
						type: "string",
						description: "Key name (Enter, ArrowDown, Escape, Tab, etc.)",
					},
				},
				required: ["key"],
			},
		},
		{
			name: "evaluate",
			description:
				"Execute arbitrary JavaScript in the browser page context. Returns the result (must be JSON-serializable).",
			inputSchema: {
				type: "object",
				properties: {
					javascript_code: {
						type: "string",
						description: "JavaScript code to evaluate",
					},
				},
				required: ["javascript_code"],
			},
		},
		{
			name: "get_links",
			description:
				"Return all links on the current page as an array of { href, text }.",
			inputSchema: { type: "object", properties: {} },
		},
		{
			name: "get_images",
			description:
				"Return all images on the current page as an array of { src, alt, width, height }.",
			inputSchema: { type: "object", properties: {} },
		},
		{
			name: "select_option",
			description:
				"Select an option in a <select> dropdown by value attribute.",
			inputSchema: {
				type: "object",
				properties: {
					selector: {
						type: "string",
						description: "CSS selector of the <select> element",
					},
					value: { type: "string", description: "Option value to select" },
				},
				required: ["selector", "value"],
			},
		},
		{
			name: "go_back",
			description: "Navigate back in browser history.",
			inputSchema: { type: "object", properties: {} },
		},
		{
			name: "go_forward",
			description: "Navigate forward in browser history.",
			inputSchema: { type: "object", properties: {} },
		},
		{
			name: "reload_page",
			description: "Reload the current page.",
			inputSchema: { type: "object", properties: {} },
		},
	],
}));

// --- Tool Dispatch ---

server.setRequestHandler(CallToolRequestSchema, async (request) => {
	const { name, arguments: args } = request.params;

	try {
		switch (name) {
			// -- Original 6 --
			case "open_page": {
				const p = await initPlaywright();
				await p.goto(args.url, {
					waitUntil: "domcontentloaded",
					timeout: 30000,
				});
				const title = await p.title();
				return {
					content: [
						{ type: "text", text: `Opened: ${args.url}\nTitle: ${title}` },
					],
				};
			}

			case "click": {
				requirePage();
				await page.click(args.selector, { timeout: 10000 });
				return {
					content: [{ type: "text", text: `Clicked: ${args.selector}` }],
				};
			}

			case "fill": {
				requirePage();
				await page.fill(args.selector, args.text, { timeout: 10000 });
				return {
					content: [
						{
							type: "text",
							text: `Filled "${args.text}" into ${args.selector}`,
						},
					],
				};
			}

			case "get_content": {
				requirePage();
				const html = await page.content();
				const text = cleanText(html);
				const title = await page.title();
				return {
					content: [
						{
							type: "text",
							text: `Title: ${title}\n\n${text.substring(0, 5000)}`,
						},
					],
				};
			}

			case "screenshot": {
				requirePage();
				const buf = await page.screenshot({
					fullPage: args?.fullPage || false,
					type: "png",
				});
				return {
					content: [
						{
							type: "image",
							data: buf.toString("base64"),
							mimeType: "image/png",
						},
						{ type: "text", text: "Screenshot taken" },
					],
				};
			}

			case "close_page": {
				if (page) {
					await page.close();
					page = null;
				}
				if (context) {
					await context.close();
					context = null;
				}
				if (browser) {
					await browser.close();
					browser = null;
				}
				return { content: [{ type: "text", text: "Browser closed" }] };
			}

			// -- New 10 --

			case "wait_for_selector": {
				requirePage();
				const timeout = args.timeout_ms ?? 30000;
				await page.waitForSelector(args.selector, { timeout });
				return {
					content: [{ type: "text", text: `Element found: ${args.selector}` }],
				};
			}

			case "scroll": {
				requirePage();
				const dir = args.direction ?? "down";
				const px = args.pixels ?? 500;
				const delta = dir === "up" ? -Math.abs(px) : Math.abs(px);
				await page.evaluate((d) => window.scrollBy(0, d), delta);
				return {
					content: [
						{ type: "text", text: `Scrolled ${dir} by ${Math.abs(delta)}px` },
					],
				};
			}

			case "press_key": {
				requirePage();
				await page.keyboard.press(args.key);
				return {
					content: [{ type: "text", text: `Pressed key: ${args.key}` }],
				};
			}

			case "evaluate": {
				requirePage();
				const result = await page.evaluate((code) => {
					try {
						return { ok: true, value: eval(code) };
					} catch (e) {
						return { ok: false, error: e.message };
					}
				}, args.javascript_code);

				if (!result.ok) {
					return {
						content: [{ type: "text", text: `JS Error: ${result.error}` }],
						isError: true,
					};
				}
				const serialized = JSON.stringify(result.value, null, 2);
				return { content: [{ type: "text", text: serialized ?? "undefined" }] };
			}

			case "get_links": {
				requirePage();
				const links = await page.evaluate(() =>
					Array.from(document.querySelectorAll("a[href]")).map((a) => ({
						href: a.href,
						text: a.innerText.trim(),
					})),
				);
				return {
					content: [{ type: "text", text: JSON.stringify(links, null, 2) }],
				};
			}

			case "get_images": {
				requirePage();
				const images = await page.evaluate(() =>
					Array.from(document.querySelectorAll("img")).map((img) => ({
						src: img.src,
						alt: img.alt,
						width: img.naturalWidth,
						height: img.naturalHeight,
					})),
				);
				return {
					content: [{ type: "text", text: JSON.stringify(images, null, 2) }],
				};
			}

			case "select_option": {
				requirePage();
				await page.selectOption(args.selector, args.value);
				return {
					content: [
						{
							type: "text",
							text: `Selected "${args.value}" in ${args.selector}`,
						},
					],
				};
			}

			case "go_back": {
				requirePage();
				await page.goBack();
				const title = await page.title();
				return {
					content: [
						{ type: "text", text: `Navigated back. Current title: ${title}` },
					],
				};
			}

			case "go_forward": {
				requirePage();
				await page.goForward();
				const title = await page.title();
				return {
					content: [
						{
							type: "text",
							text: `Navigated forward. Current title: ${title}`,
						},
					],
				};
			}

			case "reload_page": {
				requirePage();
				await page.reload();
				const title = await page.title();
				return {
					content: [{ type: "text", text: `Page reloaded. Title: ${title}` }],
				};
			}

			default:
				throw new Error(`Unknown tool: ${name}`);
		}
	} catch (error) {
		return {
			content: [{ type: "text", text: `Error: ${error.message}` }],
			isError: true,
		};
	}
});

// --- Start ---

async function main() {
	const transport = new StdioServerTransport();
	await server.connect(transport);
	console.error("Playwright MCP Server running (v2.0.0, 16 tools)");
}

main().catch(console.error);
