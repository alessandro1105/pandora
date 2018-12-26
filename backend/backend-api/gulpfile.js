const {task, series, parallel, watch, src, dest} = require('gulp');
const del = require('del');
const path = require('path');
const clean = require('gulp-clean');

// Folders
var PATH = {
	SRC: './backend/backend-api/src', // Src directory
	BUILD: './backend/backend-api/build', // Build directory
}

// Clean build directory
task('clean', function () {
	return src(PATH.BUILD)
		.pipe(clean());
});


// Build tasks
// -------------------------

// Copy content from src to build folder
function copy() {
	return src(PATH.SRC + '/**/*', { allowEmpty: true })
		.pipe(dest(PATH.BUILD));
}

// Copy htaccess from src to build
function htaccess() {
	return src(PATH.SRC + '/.htaccess', { allowEmpty: true })
		.pipe(dest(PATH.BUILD));
}

// Build project in development environment
task('build-dev', parallel(
	copy,
	htaccess
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
	// Watch all files
	watch(PATH.SRC + '/**/*')
		.on('add', copy)
		.on('change', copy)
		.on('unlink', function (filename) {
			deleteFile(filename, PATH.SRC, PATH.BUILD);
		});

	// Watch htaccess file
	watch(PATH.SRC + '/.htaccess')
		.on('add', htaccess)
		.on('change', htaccess)
		.on('unlink', function (filename) {
			deleteFile(filename, PATH.SRC, PATH.BUILD);
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