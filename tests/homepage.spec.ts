import { test, expect } from "@playwright/test";

test("homepage has title", async ({ page }) => {
	await page.goto("/");
	await expect(page).toHaveTitle(/Cửa Hàng Điện Thoại/);
});

test("homepage has products", async ({ page }) => {
	await page.goto("/");
	const products = page.locator(".card-title");
	await expect(products.first()).toBeVisible();
});

test("filter sidebar visible on desktop", async ({ page }) => {
	await page.goto("/");
	const filterColumn = page.locator("#filterColumn");
	await expect(filterColumn).toBeVisible();
});
