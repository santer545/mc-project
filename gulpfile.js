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
    critical = require('critical'),
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
gulp.task('mainStylesAB', function() {
    return gulp.src('sass/main-page-ab.scss')
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

gulp.task('mainStylesABMobile', function() {
    return gulp.src('sass/main-page-ab-mobile.scss')
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
    return gulp.src('sass/content-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())

        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('contentMobileStyles', function() {
    return gulp.src('sass/content-page-mobile.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())

        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('registrationStyles', function() {
    return gulp.src('sass/registration-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())

        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('registrationMobileStyles', function() {
    return gulp.src('sass/registration-page-mobile.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())

        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('personalStyles', function() {
    return gulp.src('sass/personal-page.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ style: 'expanded' }))
        // .pipe(purify(['dist/css/**/*.css']))
        .pipe(autoprefixer('last 5 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(cssnano())
        .pipe(gulp.dest('dist/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

gulp.task('personalMobileStyles', function() {
    return gulp.src('sass/personal-page-mobile.scss')
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

gulp.task('Lending1_Styles', function() {
    return gulp.src('sass/lending1-page.scss')
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

gulp.task('Credit_history', function() {
    return gulp.src('sass/credit-history.scss')
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

gulp.task('critical-login', function() {
    critical.generate({
        inline: true,
        extract: true,
        css: ['dist/css/main-page.min.css'],
        base: './dist',
        src: './index.html',
        ignore: ['@font-face', /url\(/],
        dest: 'dist/test-critical.html',
        dimensions: [
        {
            width: 767,
        },
        ]
    });
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
            sourceDir + '/jquery.mousewheel.js',
            sourceDir + '/jquery.jscrollpane.min.js',
            sourceDir + '/slick.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/modal.js',
            sourceDir + '/jquery.maskedinput.min.js',
            sourceDir + '/device.min.js',
            
            sourceDir + '/main-validate.js',
            sourceDir + '/svg.min.js',
            sourceDir + '/home-main.js',
            sourceDir + '/functions.js',
            sourceDir + '/googleApi.js',
            sourceDir + '/forWidget.js',
            sourceDir + '/fingerprint2.js',
            sourceDir + '/jquery.lazy.js',
            sourceDir + '/client.min.js',
            sourceDir + '/downloadJS.js',
            sourceDir + '/iovation.js',

        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('all-home.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});

// Scripts
gulp.task('personalScripts', function() {

    return gulp.src([
            sourceDir + '/jquery-2.2.1.min.js',
            sourceDir + '/slick.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/modal.js',
            sourceDir + '/dropdown.js',
            sourceDir + '/jquery.selectric.min.js',
            sourceDir + '/tab.js',
            sourceDir + '/jquery.maskedinput.min.js',
            sourceDir + '/device.min.js',
            sourceDir + '/googleApi.js',
            sourceDir + '/forWidget.js',
            sourceDir + '/fingerprint2.js',
            sourceDir + '/validate.js',
            sourceDir + '/svg.min.js',
            sourceDir + '/personal-main.js',
            sourceDir + '/functions.js',
            sourceDir + '/jquery.lazy.js',
            sourceDir + '/client.min.js'

        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('personal-home.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});

// Scripts
gulp.task('contentScripts', function() {

    return gulp.src([
            sourceDir + '/jquery-2.2.1.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/slick.min.js',
            sourceDir + '/jquery.side-bar.js',
            sourceDir + '/modal.js',
            sourceDir + '/lightbox.js',
            sourceDir + '/dropdown.js',
            sourceDir + '/jquery.lazy.js',
            sourceDir + '/jquery.selectric.min.js',
            sourceDir + '/tab.js',
            sourceDir + '/jquery.maskedinput.min.js',
            sourceDir + '/device.min.js',
            sourceDir + '/validate.js',
            sourceDir + '/svg.min.js',
            sourceDir + '/content-main.js',
            sourceDir + '/functions.js',
            sourceDir + '/googleApi.js',
            sourceDir + '/forWidget.js',
            sourceDir + '/fingerprint2.js',
            sourceDir + '/jquery.lazy.js',
            sourceDir + '/client.min.js',
            sourceDir + '/downloadJS.js',
        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('content-home.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});


// Scripts
gulp.task('homeScriptsAB', function() {

    return gulp.src([
            sourceDir + '/jquery-2.2.1.min.js',
            sourceDir + '/slick.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/modal.js',
            sourceDir + '/jquery.maskedinput.min.js',
            sourceDir + '/jquery.mousewheel.js',
            sourceDir + '/jquery.jscrollpane.min.js',
            //sourceDir + '/device.min.js',
            //sourceDir + '/googleApi.js',
            //sourceDir + '/forWidget.js',
            //sourceDir + '/fingerprint2.js',
            sourceDir + '/main-validate.js',
            sourceDir + '/home-main-ab.js',
            //sourceDir + '/functions.js',
            sourceDir + '/jquery.lazy.js',
            //sourceDir + '/client.min.js'

        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('all-home-ab.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});

// Scripts
gulp.task('registrationScripts', function() {

    return gulp.src([
            sourceDir + '/jquery-2.2.1.min.js',
            sourceDir + '/bootstrap-slider.js',
            sourceDir + '/bootstrap-select.js',
            sourceDir + '/dropdown.js',
            sourceDir + '/tab.js',
            sourceDir + '/modal.js',
            sourceDir + '/jquery.lazy.js',
            sourceDir + '/jquery.maskedinput.min.js',
            sourceDir + '/validate.js',
            sourceDir + '/device.min.js',
            sourceDir + '/googleApi.js',
            sourceDir + '/forWidget.js',
            //sourceDir + '/jquery.selectric.min.js',
            sourceDir + '/fingerprint2.js',
            sourceDir + '/svg.min.js',
            sourceDir + '/registration-main.js',
            sourceDir + '/functions.js',
            sourceDir + '/client.min.js',
            sourceDir + '/downloadJS.js'
        ])

        //.pipe(browserify(components.scripts.options))
        .pipe(concat('all-registration.js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('dist/js'))
        .pipe(notify({ message: 'Scripts task complete' }));
});



// Default task
gulp.task('default', function() {
    gulp.start('mainStyles', 'mainStylesAB', 'contentScripts', 'homeScriptsAB', 'mainStylesABMobile', 'mainMobileStyles', 'homeScripts', 'registrationStyles', 'registrationMobileStyles', 'registrationScripts', 'personalStyles', 'personalScripts', 'personalMobileStyles', 'contentStyles', 'contentMobileStyles', 'Lending1_Styles', 'Credit_history');
});

gulp.task('server', function() {
    gulp.start('default', 'watch', 'browser-sync', 'rigger');
});

// Watch
gulp.task('watch', function() {

    // Watch .scss files
    gulp.watch('sass/**/*.scss', ['mainStylesAB', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['mainStylesABMobile', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['mainStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['mainMobileStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['registrationStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['registrationMobileStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['personalStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['personalMobileStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['contentMobileStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['Lending1_Styles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['contentStyles', browserSync.reload]);
    gulp.watch('sass/**/*.scss', ['Credit_history', browserSync.reload]);  

    // Watch .js files
    gulp.watch('js/**/*.js', ['homeScripts', browserSync.reload]);
    gulp.watch('js/**/*.js', ['homeScriptsAB', browserSync.reload]);
    gulp.watch('js/**/*.js', ['registrationScripts', browserSync.reload]);
    gulp.watch('js/**/*.js', ['personalScripts', browserSync.reload]);

    gulp.watch('js/**/*.js', ['contentScripts', browserSync.reload]);


    // Watch image files
    //gulp.watch('assets/images/**/*', ['images', browserSync.reload]);

    //gulp.watch('assets/svg/**/*', ['svgSprite', browserSync.reload]);
    gulp.watch('template/*.html', ['rigger', browserSync.reload]);

});