const gulp = require('gulp');
const autoprefixer = require('gulp-autoprefixer');
const sass = require('gulp-sass');

gulp.task('default', function () {
  return gulp
    .src('sass/*.scss')
    .pipe(sass({errLogToConsole: true, outputStyle: 'expanded'}).on('error', sass.logError))
    .pipe(autoprefixer({
      browsers: [
        "last 2 versions",
        "ie >= 9",
        "Android >= 2.3",
        "ios >= 7"
      ]
    }))
    .pipe(gulp.dest('css'));
});
