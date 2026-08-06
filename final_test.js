const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on('pageerror', err => errors.push(err.message));

  await page.goto('file:///home/claude/work/final/ناوراکار-محاسبه‌گر-واردات-خودرو.html');
  await page.waitForTimeout(300);
  let sw = await page.evaluate(() => document.documentElement.scrollWidth);
  let cw = await page.evaluate(() => document.documentElement.clientWidth);
  console.log('mobile start - overflow:', sw > cw, `(${sw} vs ${cw})`);

  await page.click('.start-opt[data-mode="contact"]');
  await page.waitForTimeout(200);
  sw = await page.evaluate(() => document.documentElement.scrollWidth);
  cw = await page.evaluate(() => document.documentElement.clientWidth);
  console.log('mobile contact - overflow:', sw > cw, `(${sw} vs ${cw})`);
  console.log('contact rows:', await page.locator('.contact-row').count());
  const iconSizes = await page.evaluate(() => Array.from(document.querySelectorAll('.contact-row .cr-btn')).map(b=>{const r=b.getBoundingClientRect();return Math.round(r.width)+'x'+Math.round(r.height);}));
  console.log('button sizes:', iconSizes);

  await browser.close();

  const page2 = await (await chromium.launch()).newPage({ viewport: { width: 390, height: 844 } });
  await page2.goto('file:///home/claude/work/final/lead-form.php').catch(()=>{});
  console.log('note: lead-form.php is PHP, file:// cannot render it (expected)');
  console.log('errors:', errors.length ? errors.join('\n') : 'none');
})();
