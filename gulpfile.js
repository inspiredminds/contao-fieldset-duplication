var gulp = require('gulp');
var uglify = require('gulp-uglify-es').default;
var rename = require('gulp-rename');

gulp.task('build', function() {
  return gulp.src(['public/*.js', '!public/*.min.js'])
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(function (file) {
      return file.base;
    }));
});
