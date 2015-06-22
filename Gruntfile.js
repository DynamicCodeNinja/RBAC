module.exports = function(grunt){

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            scripts: {
                files: 'src/**',
                tasks: ['copy:all'],
                options: {
                    spawn: false
                }
            }
        },
        copy: {
            all: {
                files: [
                    {expand: true, src: ['src/**'], dest: '/home/sniper7kills/websites/DCN-CMS/vendor/dcn/rbac/'}
                ]
            }
        },
        default: {

        }
    });

    grunt.registerTask('default', []);
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');

};