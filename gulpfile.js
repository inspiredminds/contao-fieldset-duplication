var gulp = require('gulp');
var uglify = require('gulp-uglify-es').default;
var rename = require('gulp-rename');

gulp.task('build', function() {
  return gulp.src(['src/Resources/public/*.js', '!src/Resources/public/*.min.js'])
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(function (file) {
      return file.base;
    }));
});
