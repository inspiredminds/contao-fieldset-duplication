var gulp = require('gulp');
var plumber = require('gulp-plumber');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');

gulp.task('build', function() {
  return gulp.src(['src/Resources/public/*.js', '!src/Resources/public/*.min.js'])
    .pipe(plumber())
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(function (file) {
      return file.base;
    }));
});
