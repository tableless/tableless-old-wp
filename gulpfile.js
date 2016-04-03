'use strict';

var themeTableless = "./wp-content/themes/tableless/"
var uploadsFolder = "./wp-content/uploads/"

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function () {
  gulp.src(themeTableless + '**/*.sass')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./wp-content/themes/tableless/'))
});

gulp.task('default', function () {
});

gulp.task('watch', function () {
  gulp.watch('./wp-content/themes/tableless/**/*.sass', ['sass']);
});
