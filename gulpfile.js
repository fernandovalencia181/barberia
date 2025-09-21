import path from 'path';
import fs from 'fs';
import { glob } from 'glob';
import { src, dest, watch, series, parallel } from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import terser from 'gulp-terser';
import sharp from 'sharp';
import { deleteAsync } from 'del';
import rev from 'gulp-rev'; // Para cache busting

const sass = gulpSass(dartSass);

const paths = {
    scss: 'src/scss/**/*.scss',
    js: 'src/js/**/*.js',
    img: 'src/img/**/*'
};

// ---------------------------
// Limpiar build
// ---------------------------
export function limpiar() {
    return deleteAsync(['./public/build/**', '!./public/build']);
}

// ---------------------------
// CSS
// ---------------------------
export function css() {
    return src(paths.scss, { sourcemaps: process.env.NODE_ENV !== 'production' })
        .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
        .pipe(process.env.NODE_ENV === 'production' ? rev() : dest('./public/build/css', { sourcemaps: '.' }))
        .pipe(dest('./public/build/css', { sourcemaps: process.env.NODE_ENV !== 'production' }));
}

// ---------------------------
// JS
// ---------------------------
export function js() {
    let stream = src(paths.js);
    stream = stream.pipe(terser());
    if (process.env.NODE_ENV === 'production') {
        stream = stream.pipe(rev());
    }
    return stream.pipe(dest('./public/build/js'));
}

// ---------------------------
// Imágenes
// ---------------------------
async function procesarImagen(file, outputSubDir) {
    if (!fs.existsSync(outputSubDir)) fs.mkdirSync(outputSubDir, { recursive: true });

    const baseName = path.basename(file, path.extname(file));
    const extName = path.extname(file).toLowerCase();

    if (extName === '.svg') {
        fs.copyFileSync(file, path.join(outputSubDir, `${baseName}${extName}`));
    } else {
        const options = { quality: 80 };
        await Promise.all([
            sharp(file).jpeg(options).toFile(path.join(outputSubDir, `${baseName}${extName}`)),
            sharp(file).webp(options).toFile(path.join(outputSubDir, `${baseName}.webp`)),
            sharp(file).avif().toFile(path.join(outputSubDir, `${baseName}.avif`))
        ]);
    }
}

export async function imagenes() {
    const srcDir = './src/img';
    const buildDir = './public/build/img';
    const files = await glob(paths.img);

    await Promise.all(files.map(file => {
        const relativePath = path.relative(srcDir, path.dirname(file));
        const outputSubDir = path.join(buildDir, relativePath);
        return procesarImagen(file, outputSubDir);
    }));
}

// ---------------------------
// Watch (solo desarrollo)
// ---------------------------
export function dev() {
    watch(paths.scss, css);
    watch(paths.js, js);
    watch(paths.img, imagenes);
}

// ---------------------------
// Tareas
// ---------------------------
export const build = series(limpiar, parallel(js, css, imagenes));
export default series(build, dev);