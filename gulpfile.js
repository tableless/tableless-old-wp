'use strict';

var themeTableless = "./wp-content/themes/tableless/",
    jsFolder       = "./wp-content/themes/tableless/assets/js/",
    cssFolder      = "./wp-content/themes/tableless/assets/css/";

var gulp       = require('gulp'),
    sass       = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    jsmin      = require('gulp-jsmin'),
    jshint     = require('gulp-jshint'),
    babel      = require('gulp-babel'),
    rename     = require('gulp-rename'),
    stylish    = require('jshint-stylish');
 
gulp.task('sass', function () {
  gulp.src(themeTableless + '**/*.sass')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./wp-content/themes/tableless/'))
});

gulp.task('babel', function() {
  gulp.src(jsFolder + 'scripts.js')
  .pipe(jshint())
  .pipe(jshint.reporter(stylish))
  .pipe(babel())
  .pipe(jsmin())
  .pipe(rename({suffix: '.min'}))
  .pipe(gulp.dest(jsFolder));
})

gulp.task('watch', function () {
  gulp.watch(themeTableless + '**/*.sass', ['sass']);
  gulp.watch(jsFolder + 'scripts.js', ['babel']);
});

gulp.task('default', ['watch'])
