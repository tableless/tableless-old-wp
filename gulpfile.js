'use strict';

var themeTableless = "./wp-content/themes/tableless/";
var jsFolder = "./wp-content/themes/tableless/assets/js/";
var cssFolder = "./wp-content/themes/tableless/assets/css/";

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
 
gulp.task('sass', function () {
  gulp.src(themeTableless + '**/*.sass')
    .pipe(sourcemaps.init())
      .pipe(sass().on('error', sass.logError))
      .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest('./wp-content/themes/tableless/'));
});

gulp.task('jsmin', function() {
  gulp.src(jsFolder + 'scripts.js')
    .pipe(sourcemaps.init())
      .pipe(uglify({preserveComments: 'license'}))
      .pipe(rename({suffix: '.min'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest(jsFolder));
});

gulp.task('watch', function () {
  gulp.watch(themeTableless + '**/*.sass', ['sass']);
  gulp.watch(jsFolder + 'scripts.js', ['jsmin']);
});

gulp.task('default', ['compilar-sass', 'compilar-js', 'watch']);