/*!
 * gulp
 * $ npm install gulp-ruby-sass gulp-autoprefixer gulp-cssnano gulp-jshint gulp-concat gulp-uglify gulp-imagemin gulp-notify gulp-rename gulp-livereload gulp-cache del --save-dev
 */

// Load plugins
var gulp = require('gulp'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    cssnano = require('gulp-cssnano'),
    uglify = require('gulp-uglify'),
    minify = require('gulp-minify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    notify = require('gulp-notify'),
    cache = require('gulp-cache'),
    browserSync = require('browser-sync'),
    del = require('del'),
    replace = require('gulp-replace'),
    sourcemaps = require('gulp-sourcemaps'),
    rigger = require('gulp-rigger');

// Styles
gulp.task('mainStyles', function() {
    return gulp.src('sass/main-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())
        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

// Styles
gulp.task('mainMobileStyles', function() {
    return gulp.src('sass/main-page-mobile.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())
        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('contentStyles', function() {
    return gulp.src('sass/content-page/content-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/content-page/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())
        
        .pipe(gulp.dest('dist/content-page/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('personalStyles', function() {
    return gulp.src('sass/personal-page/personal-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/personal-page/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())
        
        .pipe(gulp.dest('dist/personal-page/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});


gulp.task('rigger', function() {
    gulp.src('template/*.html')
        .pipe(rigger())
        .pipe(gulp.dest('dist/'));
});



// Local server
gulp.task('browser-sync', function() {
    browserSync.init({
        server: {
            baseDir: "./dist"
        }
    });
});





// настройки путей к файлам
var rootDir = '.';
var sourceDir = rootDir + '/js'; // здесь хранятся все исходники
var destDir = rootDir + '/dist'; // здесь хранится все на выходе




// Scripts
gulp.task('homeScripts', function() {

    return gulp.src([
            sourceDir + '/jquery-2.2.1.min.js',
            sourceDir + '/slick.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/modal.js',
            sourceDir + '/jquery.mask.min.js',
            sourceDir + '/jquery.mousewheel.js',
            sourceDir + '/jquery.jscrollpane.min.js',
            sourceDir + '/function.js',
            sourceDir + '/home-main.js',
        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('all-home.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});



// Default task
gulp.task('default', function() {
    gulp.start('mainStyles', 'mainMobileStyles', 'homeScripts');
});

gulp.task('server', function() {
    gulp.start('default', 'watch', 'browser-sync', 'rigger');
});


// Watch
gulp.task('watch', function() {

    // Watch .scss files
    gulp.watch('sass/**/*.scss', ['mainStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['mainMobileStyles', browserSync.reload]);

    // Watch .js files
    gulp.watch('js/**/*.js', ['homeScripts', browserSync.reload]);

    // Watch image files
    //gulp.watch('assets/images/**/*', ['images', browserSync.reload]);

    //gulp.watch('assets/svg/**/*', ['svgSprite', browserSync.reload]);
    gulp.watch('template/*.html', ['rigger', browserSync.reload]);

});