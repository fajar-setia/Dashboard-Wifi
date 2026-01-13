#!/usr/bin/env node
import fs from 'fs';
import path from 'path';
import sharp from 'sharp';

const cwd = process.cwd();
const publicDir = path.join(cwd, 'public', 'images');
const srcFile = path.join(publicDir, 'lifemedia.webp');

const sizes = [128, 256];

async function ensureDir(dir) {
  try {
    await fs.promises.access(dir);
  } catch (err) {
    await fs.promises.mkdir(dir, { recursive: true });
  }
}

async function build() {
  if (!fs.existsSync(srcFile)) {
    console.error('Source image not found:', srcFile);
    process.exit(1);
  }

  await ensureDir(publicDir);

  for (const w of sizes) {
    const outWebp = path.join(publicDir, `lifemedia-${w}.webp`);
    const outJpeg = path.join(publicDir, `lifemedia-${w}.jpg`);

    console.log(`Generating ${outWebp}`);
    await sharp(srcFile)
      .resize({ width: w })
      .webp({ quality: 80 })
      .toFile(outWebp);

    console.log(`Generating ${outJpeg}`);
    await sharp(srcFile)
      .resize({ width: w })
      .jpeg({ quality: 82, mozjpeg: true })
      .toFile(outJpeg);
  }

  console.log('lifemedia variants generated.');
}

build().catch(err => {
  console.error(err);
  process.exit(1);
});
