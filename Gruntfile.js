module.exports = function(grunt) {
    // Project configuration
    grunt.initConfig({
      pkg: grunt.file.readJSON('package.json'),
  
      // Example of a build task
      uglify: {
        build: {
          src: 'src/main.js',
          dest: 'dist/main.min.js'
        }
      },
      cssmin: {
        build: {
          src: 'src/style.css',
          dest: 'dist/style.min.css'
        }
      }
    });
  
    // Load the plugins that provide the "uglify" and "cssmin" tasks
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
  
    // Register the build task
    grunt.registerTask('build', ['uglify', 'cssmin']);
  };
  