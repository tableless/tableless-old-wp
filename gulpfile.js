'use strict';

var themeTableless = "./wp-content/themes/tableless/"
var jsFolder = "./wp-content/themes/tableless/assets/js/"
var cssFolder = "./wp-content/themes/tableless/assets/css/"

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var jsmin = require('gulp-jsmin');
var rename = require('gulp-rename');

gulp.task('sass', function () {
  gulp.src(themeTableless + '**/*.sass')
    .pipe(sass().on('error', sass.logError))
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(gulp.dest('./wp-content/themes/tableless/'))
});

gulp.task('jsmin', function() {
  gulp.src(jsFolder + 'scripts.js')
  .pipe(jsmin())
  .pipe(rename({suffix: '.min'}))
  .pipe(gulp.dest(jsFolder));
})

gulp.task('watch', function () {
  gulp.watch(themeTableless + '**/*.sass', ['sass']);
  gulp.watch(jsFolder + 'scripts.js', ['jsmin']);
});

gulp.task('default', ['watch'])
