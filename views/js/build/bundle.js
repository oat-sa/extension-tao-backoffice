module.exports = function(grunt) {

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    /**
     * Remove bundled and bundling files
     */
    clean.backofficebundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.backofficebundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : { 'taoBackOffice' : root + '/taoBackOffice/views/js' },
            modules : [{
                name: 'taoBackOffice/controller/routes',
                include : ext.getExtensionsControllers(['taoBackOffice']),
                exclude : ['mathJax', 'mediaElement'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.backofficebundle = {
        files: [
            { src: [ out + '/taoBackOffice/controller/routes.js'],  dest: root + '/taoBackOffice/views/js/controllers.min.js' },
            { src: [ out + '/taoBackOffice/controller/routes.js.map'],  dest: root + '/taoBackOffice/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('backofficebundle', ['clean:backofficebundle', 'requirejs:backofficebundle', 'copy:backofficebundle']);
};
