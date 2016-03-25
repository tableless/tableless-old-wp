'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function () {
  gulp.src('./**/*.sass')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./'))
});

gulp.task('default', function () {
});

gulp.task('watch', function () {
  gulp.watch('./**/*.sass', ['sass']);
});
