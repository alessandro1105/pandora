const {task, series, src} = require('gulp');
const clean = require('gulp-clean');
const del = require('del');
const path = require('path');

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

// Build project in development environment
task('build-dev', function (cb) {
	cb();
});


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
