path = 'poke'
autoPrefixOptions = {browsers: ["last 2 versions", "Android 4.3", "ie 9"]}

module.exports = (grunt) ->
  grunt.initConfig
    useminPrepare:
      html: ['src/Devrun/CmsModule/Presenters/templates/@layout.latte']  #    layout ze kterého se čerpají zdroje
      options:
        dest: '.'

    uglify:
      options: {
        compress: {
          global_defs: {
            "DEBUG": false
          },
          dead_code: true
        }
      }

    netteBasePath: {
      task:
        basePath: 'www'
        options:
          searchPattern: '{$cmsPath}'
          removeFromPath: ['src/Devrun/CmsModule/Presenters/templates/']   # soubory se sloučí do seznamu s absolutními cesty, tato cesta se pak odstraní
    }

  # These plugins provide necessary tasks.
  grunt.loadNpmTasks 'grunt-contrib-concat'
  grunt.loadNpmTasks 'grunt-contrib-uglify'
  grunt.loadNpmTasks 'grunt-contrib-cssmin'
  grunt.loadNpmTasks 'grunt-usemin'
  grunt.loadNpmTasks 'grunt-nette-basepath'

  # Default task.
  grunt.registerTask 'cms-default', [
    'useminPrepare'
    'netteBasePath',
    'concat'
    'uglify'
    'cssmin'
  ]