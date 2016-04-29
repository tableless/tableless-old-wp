'use strict';

var paths = {
  PASTA_TEMA: "./wp-content/themes/tableless/",
  PASTA_JS: "./wp-content/themes/tableless/assets/js/"
};

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
 
gulp.task('compilar-sass', function () {
  gulp.src(paths.PASTA_TEMA + '**/*.sass')
    .pipe(sourcemaps.init())
      .pipe(sass().on('error', sass.logError))
      .pipe(sass({outputStyle: 'compressed'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest(paths.PASTA_TEMA));
});

gulp.task('comprimir-js', function() {
  gulp.src(paths.PASTA_JS + 'scripts.js')
    .pipe(sourcemaps.init())
      .pipe(uglify({preserveComments: 'license'}))
      .pipe(rename({suffix: '.min'}))
    .pipe(sourcemaps.write('maps'))
    .pipe(gulp.dest(paths.PASTA_JS));
});

gulp.task('watch', function () {
  gulp.watch(paths.PASTA_TEMA + '**/*.sass', ['compilar-sass']);
  gulp.watch(paths.PASTA_JS + 'scripts.js', ['comprimir-js']);
});

gulp.task('default', ['compilar-sass', 'comprimir-js', 'watch']);