const {task, registry, src} = require('gulp');
const clean = require('gulp-clean');
const HubRegistry = require('gulp-hub');

// Folders of the project
var PROJECT = {
	TMP: './tmp' // Src directory
}

// Clean
// -------------------------

// Clean TMP folder
task('clean-tmp', function () {
	return src(PROJECT.TMP)
		.pipe(clean());
});


// Registry
// -------------------------

// Create hub
var hub = new HubRegistry([
	//'./backend/backend-api/gulpfile.js',
	'./persistent/persistent-api/gulpfile.js',
	'./storage/storage-api/gulpfile.js',
	//'./user/user-api/gulpfile.js',
	//'./webapp/gulpfile.js'
]);

// Register custom registry
registry(hub);