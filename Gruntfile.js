module.exports = function(grunt) {

    var wwwDir = 'www/';
    var layoutFile = 'app/modules/cms-module/src/FrontModule/Presenters/templates/@layout.latte';
    var autoPrefixOptions = {browsers: ["last 2 versions", "Android 4.3", "ie 9"]};
    var AutoPrefixPlugin = require('less-plugin-autoprefix');
    var autoPrefix = new AutoPrefixPlugin(autoPrefixOptions);
    var inlineUrlsPlugin = require('less-plugin-inline-urls');
    var groupMediaQueries = require('less-plugin-group-css-media-queries');

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		copy: {
			main: {
				files: [
					{expand: true, cwd: 'bower_components/nette-forms/src/assets/', src: 'netteForms.js', dest: 'www/assets/nette-forms/'},
					{expand: true, cwd: 'bower_components/bootstrap/dist/', src: '**', dest: 'www/assets/bootstrap/'},
					{expand: true, cwd: 'bower_components/jasny-bootstrap/dist/', src: '**', dest: 'www/assets/jasny-bootstrap/'},
					{expand: true, cwd: 'bower_components/font-awesome/css/', src: 'font-awesome.min.css', dest: 'www/assets/font-awesome/css/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/font-awesome/', src: 'fonts/**', dest: 'www/assets/font-awesome/'},
					{expand: true, cwd: 'bower_components/jquery/', src: 'jquery.min.js', dest: 'www/assets/jquery/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/jquery-ui/', src: 'themes/base/**', dest: 'www/assets/jquery-ui/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/jquery-ui/', src: 'jquery-ui.min.js', dest: 'www/assets/jquery-ui/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/jquery/', src: 'jquery-migrate.min.js', dest: 'www/assets/jquery/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/jquery-ui/ui/minified/', src: 'jquery-ui.min.js', dest: 'www/assets/jquery-ui/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/jquery-simulate/', src: 'jquery.simulate.js', dest: 'www/assets/jquery-simulate/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/smalot-bootstrap-datetimepicker/css/', src: 'bootstrap-datetimepicker.min.css', dest: 'www/assets/bootstrap-datetimepicker/css/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/smalot-bootstrap-datetimepicker/js/', src: 'bootstrap-datetimepicker.min.js', dest: 'www/assets/bootstrap-datetimepicker/js/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/smalot-bootstrap-datetimepicker/js/locales/', src: '**', dest: 'www/assets/bootstrap-datetimepicker/js/locales/'},
					{expand: true, cwd: 'bower_components/jquery-hashchange/', src: 'jquery.ba-hashchange.min.js', dest: 'www/assets/jquery-hashchange/', filter: 'isFile'},
					{expand: true, cwd: 'bower_components/select2/', src: '**', dest: 'www/assets/select2/'},
					{expand: true, cwd: 'bower_components/typeahead.js/dist/', src: 'typeahead.bundle.min.js', dest: 'www/assets/typeahead.js/'},
					{expand: true, cwd: 'bower_components/holderjs/', src: 'holder.js', dest: 'www/assets/holder/'},
					{expand: true, cwd: 'node_modules/hogan.js/web/builds/3.0.2/', src: 'hogan-3.0.2.min.js', dest: 'www/assets/hogan/'},
					{expand: true, cwd: 'bower_components/nette.ajax.js/', src: 'nette.ajax.js', dest: 'www/assets/nette.ajax.js/'},
					{expand: true, cwd: 'bower_components/history.nette.ajax.js/client-side', src: 'history.ajax.js', dest: 'www/assets/history.ajax.js/'},
					{expand: true, cwd: 'bower_components/qtip2/basic/', src: '*', dest: 'www/assets/jquery.qtip/'},
				]
			}
		},

	  	uglify: {
	  		options: {
		        beautify: true
		    },
			js: {
				files: {
					'Resources/public/js/application.min.js': ['Resources/public/js/*.js', '!js/*.min.js']
				}
			}
		},

		cssmin: {
			combine: {
				files: {
					'Resources/public/css/application.min.css': ['Resources/public/css/application.css']
				}
			},
			minify: {
				expand: true,
				cwd: 'www/css/',
				src: ['index.css', 'legacy_ie.css', 'custom.css'],
				dest: 'www/webcache/'
			}
		},

		imagemin: {
			dynamic: {
				options: {
					optimizationLevel: 3
				},
				files: [{
					expand: true,
					cwd: 'Resources/public/',
					src: ['**/*.{png,jpg,gif}'],
					dest: 'Resources/public/'
				}]
			}
		},

        autoprefixer: {
            dist: {
                options: {
                    browsers: ['last 1 version', '> 1%', 'ie 8', 'ie 7']
                },
                files: {
                    'www/css/index.css': ['www/css/index.css'],
                    'www/css/custom.css': ['www/css/custom.css']
                }
            }
        },


        less: {
            development: {

                options: {
                    paths: ["css"],
                    optimization: 2,
                    sourceMap: true,
                    outputSourceFiles: true,
                    plugins: [
                        autoPrefix,
                        groupMediaQueries,
                    ]
                },
                files: {
                    "app/modules/front-module/resources/build/css/index.css": "app/modules/front-module/resources/src/less/index.less",
                    "app/modules/front-module/resources/build/css/bootstrap.css": "app/modules/front-module/resources/src/less/bootstrap.less",
                }
            },
            production: {
                options: {
                    compress: false,
                    paths: ["css"],
                    plugins: [
                        autoPrefix,
                        inlineUrlsPlugin,
                        groupMediaQueries,
                        new (require('less-plugin-clean-css'))(),
                    ],
                    modifyVars: {
                        //imgPath: '"http://mycdn.com/path/to/images"',
                        //bgColor: 'red'
                    }
                },
                files: {
                    "app/modules/front-module/resources/build/css/index.min.css": "app/modules/front-module/resources/src/less/index.less",
                    "app/modules/front-module/resources/build/css/bootstrap.min.css": "app/modules/front-module/resources/src/less/bootstrap.less",
                }
            }
        },


        shell: {
            options: {
                stderr: false
            },
            projectCommands: {
                command: 'php www/index.php',
                options: {
                    stdout: false,
                    stderr: false,
                    execOptions: {
                        encoding : 'utf8'
                    }
                }
            },
            validateSchema: {
                command: 'php www/index.php orm:validate-schema'
            },
            dumpSchema: {
                command: 'php www/index.php orm:schema-tool:update --dump-sql'
            },
			dumpSaveUpdateSchemaSql: {
				command: 'php www/index.php orm:schema-tool:update --dump-sql > update.sql'
			},
			dumpSaveCreateSchemaSql: {
				command: 'php www/index.php orm:schema-tool:create --dump-sql > install.sql'
			},
            updateSchema: {
                command: 'php www/index.php orm:schema-tool:update --force'
            }
        },

        sync: {
            main: {
                files: [{
                    cwd: 'devrun/framework/src',
                    src: [
                        '**' /* Include everything */
                        // '!**/*.txt' /* but exclude txt files */
                    ],
                    dest: '/var/www/html/devrun-framework/src'
                }],
                updateAndDelete: true,
                compareUsing: "md5",
                // pretend: true, // Don't do any IO. Before you run the task with `updateAndDelete` PLEASE MAKE SURE it doesn't remove too much.
                verbose: true // Display log messages when copying files
            }
        },

        // make a zipfile
        compress: {
            // main: {
            //     options: {
            //         archive: '/var/www/archives/nivea/care-0.1.zip'
            //     },
            //     files: [
            //         {expand: true, src: ['app/**', 'www/**'], dest: '/'},
            //         {expand: true, src: ['*.json', '*.js', '.gitignore'], dest: '/'},
            //     ]
            // },
            framework: {
                options: {
                    archive: '/var/www/archives/devrun/framework-0.1.zip'
                },
                files: [
                    {expand: true, cwd: '/var/www/html/devrun-framework/Devrun/', src: ['**'], dest: 'Devrun/', filter: 'isFile'},
                    {expand: true, cwd: '/var/www/html/devrun-framework/', src: ['*.json', '*.js', '.gitignore'], dest: '/'}
                ]
            },
            cmsModule: {
                options: {
                    archive: '/var/www/archives/devrun/cms-module_0.1.zip'
                },
                files: [
                    {expand: true, cwd: '/var/www/html/devrun-cms_module/', src: ['**'], dest: '/', filter: 'isFile'}
                ]
            },
            articleModule: {
                options: {
                    archive: '/var/www/archives/devrun/article-module_0.1.zip'
                },
                files: [
                    {expand: true, cwd: '/var/www/html/devrun-article_module/', src: ['**'], dest: '/', filter: 'isFile'}
                ]
            },
            catalogModule: {
                options: {
                    archive: '/var/www/archives/devrun/catalog-module_0.1.zip'
                },
                files: [
                    {expand: true, cwd: '/var/www/html/devrun-catalog_module/', src: ['**'], dest: '/', filter: 'isFile'}
                ]
            }
        }



        //watch: {
		//	css: {
		//		files: ['Resources/public/*/*.scss'],
		//		tasks: ['autoprefixer', 'cssmin']  // 'sass',
		//	},
        //
		//	imagemin: {
		//		files: [
		//			'Resources/public/*/*.jpg',
		//			'Resources/public/*/*.jpeg',
		//			'Resources/public/*/*.png',
		//			'Resources/public/*/*.gif'
		//		],
		//		tasks: ['imagemin']
		//	}
		//}
	});

    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-multi-dest');
	grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-sync');

    grunt.registerTask('default', 'sync');
	grunt.registerTask('copy assets', ['copy'  ]);
	//grunt.registerTask('default', ['copy:main', 'uglify', 'autoprefixer', 'cssmin', 'imagemin']);  // 'sass',
	grunt.registerTask('watch', ['watch']);
    grunt.registerTask('terminal', ['shell']);
};
