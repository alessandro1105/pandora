const {task, series, src, dest, watch} = require('gulp');
const clean = require('gulp-clean');
const del = require('del');
const path = require('path');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-sass');
const plumber = require('gulp-plumber');
const flatten = require('gulp-flatten');

// Folders
var PATH = {
	SRC: './webapp/src', // Src directory
	BUILD: './webapp/build', // Build directory
}

// Clean build directory
task('clean', function () {
	return src(PATH.BUILD)
		.pipe(clean());
});


// Build tasks
// -------------------------

// Copy index.html from src to build
function index() {
	return src(PATH.SRC + '/index.html')
		.pipe(dest(PATH.BUILD));
}

// Copy app from src to build
function app() {
	return src(PATH.SRC + '/app/**/*')
		.pipe(dest(PATH.BUILD + '/app/'));
}

// Compile Sass files into css in build folder
function css() {
	return src(PATH.SRC + '/assets/scss/style.scss')
		.pipe(plumber())
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(sourcemaps.write('./maps'))
        .pipe(dest(PATH.BUILD + '/assets/css/'));
}

// Compile and copy vendors
function vendors(cb) {
	// Fonts
	src(PATH.SRC + '/vendors/*/fonts/*')
		.pipe(flatten())
		.pipe(dest(PATH.BUILD + '/vendors/fonts'));

	// Sass vendors bundle
	src([
		PATH.SRC + '/vendors/mdi/scss/materialdesignicons.scss'
	])
		.pipe(sourcemaps.init())
		.pipe(sass())
		.pipe(sourcemaps.write('./maps'))
		.pipe(dest(PATH.BUILD + '/vendors/css/'));

	cb();
}

// Copy images
function images() {
	return src(PATH.SRC + '/assets/images/**/*')
		.pipe(dest(PATH.BUILD + '/assets/images'));
}

// Copy angular files from src to build
function angular() {
	return src(PATH.SRC + '/vendors/angular/**/*')
        .pipe(dest(PATH.BUILD + '/vendors/js/'));
}

// Compile and copy vendors related files (should be static files)
function vendors(cb) {
	// Fonts
	src(PATH.SRC + '/vendors/*/fonts/*')
		.pipe(flatten())
		.pipe(dest(PATH.BUILD + '/vendors/fonts'));

	// Sass vendors bundle
	src([PATH.SRC + '/vendors/mdi/scss/materialdesignicons.scss'])
		.pipe(sourcemaps.init())
		.pipe(sass())
		.pipe(sourcemaps.write('./maps'))
		.pipe(dest(PATH.BUILD + '/vendors/css/'));

	cb();
}

// Build project in development environment
task('build-dev', series(
	index,
	app,
	css,
	images,
	angular,
	vendors
));


// Watchers
// -------------------------

// Function to remove from build folder a deleted source file to prevent polluting build folder
function deleteFile(filename, source, build) {
	var pathFromSource = path.relative(path.resolve(source), filename);
	var pathFromBuild = path.resolve(build, pathFromSource);
	del.sync(pathFromBuild);
}

// Watcher
task('watch-dev', function (cb) {
	// Watch index.html
	watch(PATH.SRC + '/index.html')
		.on('change', index);

	// Watch app
	watch(PATH.SRC + '/app/**/*')
		.on('add', app)
		.on('change', app)
		.on('unlink', function (filename) {
			deleteFile(filename, PATH.SRC, PATH.BUILD)
		});

	// Watch images
	watch(PATH.SRC + '/assets/images/**/*')
		.on('add', images)
		.on('change', images)
		.on('unlink', function (filename) {
			deleteFile(filename, PATH.SRC, PATH.BUILD)
		});

	// Watch scss files
	watch(PATH.SRC + '/assets/scss/**/*.scss')
		.on('all', css);

	// Watch Angular vendors files
	watch(PATH.SRC + '/vendors/angular/*')
		.on('add', angular)
		.on('change', angular)
		.on('unlink', function (filename) {
			deleteFile(filename, PATH.SRC, PATH.BUILD)
		});

	
	
	cb();
});

// Development
// -------------------------

task('dev', series(
	task('build-dev'),
	task('watch-dev')
));

// Default
// -------------------------

task('default', task('dev'));
