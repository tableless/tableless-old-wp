'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('sass', function () {
  gulp.src('./style.sass')
    .pipe(sass().on('error', sass.logError))
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(gulp.dest('./'));
});

gulp.task('default', function () {
  // gulp.watch('./**/*.sass', ['sass']);
});

gulp.task('watch', function () {
  gulp.watch('./**/*.sass', ['sass']);
});
