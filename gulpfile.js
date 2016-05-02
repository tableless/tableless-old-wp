'use strict';

var paths = {
  THEME_DIR: "./wp-content/themes/tableless/",
  JS_DIR: "./wp-content/themes/tableless/assets/js/"
};

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
 
gulp.task('compile-sass', function () {
  gulp.src(paths.THEME_DIR + '**/*.sass')
    .pipe(sourcemaps.init())
      .pipe(sass().on('error', sass.logError))
      .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest(paths.THEME_DIR));
});

gulp.task('compress-js', function() {
  gulp.src(paths.JS_DIR + 'scripts.js')
    .pipe(sourcemaps.init())
      .pipe(uglify({preserveComments: 'license'}))
      .pipe(rename({suffix: '.min'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest(paths.JS_DIR));
});

gulp.task('watch', function () {
  gulp.watch(paths.THEME_DIR + '**/*.sass', ['compilar-sass']);
  gulp.watch(paths.JS_DIR + 'scripts.js', ['comprimir-js']);
});

gulp.task('default', ['compile-sass', 'compress-js', 'watch']);