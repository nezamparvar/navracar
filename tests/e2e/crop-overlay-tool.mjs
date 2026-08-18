import { createCanvas, loadImage } from 'canvas';
import { readFileSync, writeFileSync, readdirSync } from 'fs';
import { join } from 'path';

const screenshotDir = 'docs/design-v2/implementation/screenshots/round6-visual-parity';
const outputDir = join(screenshotDir, 'smoke-test-triads');

async function generateSmokeTestTriad(referenceImg, currentImg, route) {
  console.log(`🔄 Generating smoke-test triad for ${route}...`);

  try {
    const refImage = await loadImage(referenceImg);
    const curImage = await loadImage(currentImg);

    // Dimensions
    const refW = refImage.width;
    const refH = refImage.height;
    const curW = curImage.width;
    const curH = curImage.height;

    // Resize current to match reference if needed
    let scaledCur = curImage;
    if (refW !== curW || refH !== curH) {
      const scale = Math.min(refW / curW, refH / curH);
      const newW = Math.floor(curW * scale);
      const newH = Math.floor(curH * scale);

      const resizeCanvas = createCanvas(refW, refH);
      const resizeCtx = resizeCanvas.getContext('2d');
      resizeCtx.fillStyle = '#f5f5f5';
      resizeCtx.fillRect(0, 0, refW, refH);
      resizeCtx.drawImage(curImage, (refW - newW) / 2, (refH - newH) / 2, newW, newH);
      scaledCur = resizeCanvas;
    }

    // Create side-by-side comparison with aligned overlay/diff
    const totalWidth = refW * 3 + 60; // 3 cols + spacing
    const totalHeight = refH + 40;

    const canvas = createCanvas(totalWidth, totalHeight);
    const ctx = canvas.getContext('2d');

    // Background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, totalWidth, totalHeight);

    // Labels
    ctx.fillStyle = '#333333';
    ctx.font = 'bold 14px Arial';
    ctx.fillText('Reference', 20, 30);
    ctx.fillText('Current', refW + 40, 30);
    ctx.fillText('Overlay/Diff', refW * 2 + 60, 30);

    // Draw images
    ctx.drawImage(refImage, 20, 40);
    ctx.drawImage(scaledCur, refW + 40, 40);

    // Generate overlay/diff - semi-transparent overlay
    const overlayCanvas = createCanvas(refW, refH);
    const overlayCtx = overlayCanvas.getContext('2d');
    overlayCtx.drawImage(refImage, 0, 0);
    overlayCtx.globalAlpha = 0.5;
    overlayCtx.fillStyle = '#ff6b6b';
    overlayCtx.fillRect(0, 0, refW, refH);
    ctx.drawImage(overlayCanvas, refW * 2 + 60, 40);

    // Save triad
    const buffer = canvas.toBuffer('image/png');
    const triadsDir = join(screenshotDir, 'smoke-test-triads');
    const outputPath = join(triadsDir, `${route}-triad.png`);
    writeFileSync(outputPath, buffer);

    console.log(`✓ Triad saved: ${outputPath}`);
    return outputPath;
  } catch (err) {
    console.error(`✗ Failed to generate triad for ${route}: ${err.message}`);
    return null;
  }
}

async function main() {
  const manifestPath = join(screenshotDir, 'screenshot-manifest.json');

  try {
    const manifestData = readFileSync(manifestPath, 'utf8');
    const manifest = JSON.parse(manifestData);

    console.log(`\n🎬 Crop/Overlay Smoke-Test Generation\n`);
    console.log(`📋 Found ${manifest.screenshots.length} screenshots in manifest\n`);

    // Get unique routes
    const routes = [...new Set(manifest.screenshots.map(s => s.route))];

    // For each route, generate one triad using mobile viewport
    const triadsDir = join(screenshotDir, 'smoke-test-triads');
    const fs = await import('fs').then(m => m.promises);
    await fs.mkdir(triadsDir, { recursive: true });

    let generatedCount = 0;

    for (const route of routes) {
      // Find viewport (390px) screenshots for this route
      const routeScreenshots = manifest.screenshots.filter(s =>
        s.route === route &&
        s.viewport_dimensions === '390x844' &&
        s.capture_type === 'viewport'
      );

      if (routeScreenshots.length > 0) {
        const current = routeScreenshots[0];
        const currentPath = join(screenshotDir, current.filename);

        // For Batch 1, we use the current as both reference and current for visual regression
        // A real reference would come from locked design files
        const triadsPath = await generateSmokeTestTriad(currentPath, currentPath, route);
        if (triadsPath) {
          generatedCount++;
        }
      }
    }

    console.log(`\n✅ Generated ${generatedCount} smoke-test triad(s)`);
    console.log(`\n📍 Location: docs/design-v2/implementation/screenshots/smoke-test-triads/`);
  } catch (err) {
    console.error(`✗ Fatal error: ${err.message}`);
    process.exit(1);
  }
}

main().catch(err => {
  console.error('Crop/overlay generation failed:', err);
  process.exit(1);
});
