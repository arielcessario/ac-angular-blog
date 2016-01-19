(function () {
    'use strict';

    var scripts = document.getElementsByTagName("script");
    var currentScriptPath = scripts[scripts.length - 1].src;


    if (currentScriptPath.length == 0) {
        currentScriptPath = window.installPath + '/ac-angular-posts/includes/ac-posts.php';
    }

    angular.module('acPosts', [])
        .factory('PostService', PostService)
        .service('PostVars', PostVars)
        .factory('CommentService', CommentService)
        .service('CommentVars', CommentVars)
    ;


    PostService.$inject = ['$http', 'PostVars', '$cacheFactory', 'AcUtils'];
    function PostService($http, PostVars, $cacheFactory, AcUtils) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('ac-posts.js', '/includes/ac-posts.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;

        service.create = create;

        service.update = update;

        service.remove = remove;


        service.goToPagina = goToPagina;
        service.next = next;
        service.prev = prev;

        return service;

        //Functions
        /**
         * @description Obtiene todos los posts / No agrego usuario ya que todos los posts se están trayendo para
         * mostrar en la web, no es un dato crítico que deba permanecer oculto
         * @param callback
         * @returns {*}
         */
        function get(callback) {
            var urlGet = url + '?function=getPosts';
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de posts
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (PostVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    cachedData = $httpDefaultCache.get(urlGet);
                    callback(cachedData);
                    return;
                }
            }


            return $http.get(urlGet, {cache: true})
                .success(function (data) {
                    //console.log(data);
                    $httpDefaultCache.put(urlGet, data);
                    PostVars.clearCache = false;
                    PostVars.paginas = (data.length % PostVars.paginacion == 0) ? parseInt(data.length / PostVars.paginacion) : parseInt(data.length / PostVars.paginacion) + 1;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                    PostVars.clearCache = false;
                })
        }


        /**
         * @description Retorna la lista filtrada de posts
         * @param param -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param value
         * @param callback
         */
        function getByParams(params, values, exact_match, callback) {
            get(function (data) {
                AcUtils.getByParams(params, values, exact_match, data, callback);
            })
        }

        /** @name: remove
         * @param post_id
         * @param callback
         * @description: Elimina el post seleccionado.
         */
        function remove(post_id, callback) {
            return $http.post(url,
                {'function': 'removePost', 'post_id': post_id})
                .success(function (data) {
                    if (data !== 'false') {
                        PostVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un post.
         * @param post
         * @param callback
         * @returns {*}
         */
        function create(post, callback) {

            return $http.post(url,
                {
                    'function': 'createPost',
                    'post': JSON.stringify(post)
                })
                .success(function (data) {
                    PostVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    PostVars.clearCache = true;
                    callback(data);
                });
        }

        /** @name: update
         * @param post
         * @param callback
         * @description: Realiza update al post.
         */
        function update(post, callback) {
            return $http.post(url,
                {
                    'function': 'updatePost',
                    'post': JSON.stringify(post)
                })
                .success(function (data) {
                    PostVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }

        /**
         * Para el uso de la p�ginaci�n, definir en el controlador las siguientes variables:
         *
         vm.start = 0;
         vm.pagina = PostVars.pagina;
         PostVars.paginacion = 5; Cantidad de registros por p�gina
         vm.end = PostVars.paginacion;


         En el HTML, en el ng-repeat agregar el siguiente filtro: limitTo:appCtrl.end:appCtrl.start;

         Agregar un bot�n de next:
         <button ng-click="appCtrl.next()">next</button>

         Agregar un bot�n de prev:
         <button ng-click="appCtrl.prev()">prev</button>

         Agregar un input para la p�gina:
         <input type="text" ng-keyup="appCtrl.goToPagina()" ng-model="appCtrl.pagina">

         */


        /**
         * @description: Ir a p�gina
         * @param pagina
         * @returns {*}
         * uso: agregar un m�todo
         vm.goToPagina = function () {
                vm.start= PostService.goToPagina(vm.pagina).start;
            };
         */
        function goToPagina(pagina) {

            if (isNaN(pagina) || pagina < 1) {
                PostVars.pagina = 1;
                return PostVars;
            }

            if (pagina > PostVars.paginas) {
                PostVars.pagina = PostVars.paginas;
                return PostVars;
            }

            PostVars.pagina = pagina - 1;
            PostVars.start = PostVars.pagina * PostVars.paginacion;
            return PostVars;

        }

        /**
         * @name next
         * @description Ir a pr�xima p�gina
         * @returns {*}
         * uso agregar un metodo
         vm.next = function () {
                vm.start = PostService.next().start;
                vm.pagina = PostVars.pagina;
            };
         */
        function next() {

            if (PostVars.pagina + 1 > PostVars.paginas) {
                return PostVars;
            }
            PostVars.start = (PostVars.pagina * PostVars.paginacion);
            PostVars.pagina = PostVars.pagina + 1;
            //PostVars.end = PostVars.start + PostVars.paginacion;
            return PostVars;
        }

        /**
         * @name previous
         * @description Ir a p�gina anterior
         * @returns {*}
         * uso, agregar un m�todo
         vm.prev = function () {
                vm.start= PostService.prev().start;
                vm.pagina = PostVars.pagina;
            };
         */
        function prev() {


            if (PostVars.pagina - 2 < 0) {
                return PostVars;
            }

            //PostVars.end = PostVars.start;
            PostVars.start = (PostVars.pagina - 2 ) * PostVars.paginacion;
            PostVars.pagina = PostVars.pagina - 1;
            return PostVars;
        }


    }

    PostVars.$inject = [];
    /**
     * @description Almacena variables temporales de posts
     * @constructor
     */
    function PostVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;

        // Solo trae posts activos
        this.activos = false;


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

    }


    CommentService.$inject = ['$http', 'CommentVars', '$cacheFactory', 'AcUtils'];
    function CommentService($http, CommentVars, $cacheFactory, AcUtils) {
        //Variables
        var service = {};


        var url = currentScriptPath.replace('ac-posts.js', '/includes/ac-posts.php');

        //Function declarations
        service.get = get;
        service.getByParams = getByParams;

        service.create = create; // El carrito se crea si no hay un carrito en estado 0 que se pueda usar. Siempre primero se trae en el controlador, se verifica si existe uno en Iniciado, si no existe se crea.

        service.update = update;

        service.remove = remove;


        service.upvote = upvote;
        service.downvote = downvote;


        service.goToPagina = goToPagina;
        service.next = next;
        service.prev = prev;

        return service;

        //Functions

        /**
         * @description Obtiene todos los carritos
         * @param usuario_id, en caso traer todos los carritos, debe ser -1; Est� as� para que si el m�dulo est� en la web, nunca llegue al cliente la lista completa de pedidos;
         * @param callback
         * @returns {*}
         */
        function get(post_id, callback) {
            var urlGet = url + '?function=getComentarios&post_id=' + post_id;
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de Carritos
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (CommentVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    cachedData = $httpDefaultCache.get(urlGet);
                    callback(cachedData);
                    return;
                }
            }


            return $http.get(urlGet, {cache: true})
                .success(function (data) {
                    $httpDefaultCache.put(urlGet, data);
                    CommentVars.clearCache = false;
                    CommentVars.paginas = (data.length % CommentVars.paginacion == 0) ? parseInt(data.length / CommentVars.paginacion) : parseInt(data.length / CommentVars.paginacion) + 1;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                    CommentVars.clearCache = false;
                })
        }


        /**
         * @description Retorna la lista filtrada de Comentarios por post
         * @param params -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param values
         * @param exact_match
         * @param post_id
         * @param callback
         */
        function getByParams(params, values, exact_match, post_id, callback) {
            get(post_id, function (data) {
                AcUtils.getByParams(params, values, exact_match, data, callback);
            })
        }


        /** @name: remove
         * @param comentario_id
         * @param callback
         * @description: Elimina el carrito seleccionado.
         */
        function remove(comentario_id, callback) {
            return $http.post(url, {'function': 'removeCarrito', 'comentario_id': comentario_id})
                .success(function (data) {
                    if (data !== 'false') {
                        CommentVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un comentario.
         * @param comentario
         * @param callback
         * @returns {*}
         */
        function create(comentario, callback) {

            return $http.post(url,
                {
                    'function': 'createComentario',
                    'comentario': JSON.stringify(comentario)
                })
                .success(function (data) {
                    CommentVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    CommentVars.clearCache = true;
                    callback(data);
                });
        }


        /** @name: update
         * @param comentario
         * @param callback
         * @description: Realiza update al carrito.
         */
        function update(comentario, callback) {
            return $http.post(url,
                {
                    'function': 'updateCarrito',
                    'carrito': JSON.stringify(comentario)
                })
                .success(function (data) {
                    CommentVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }


        function upvote(){

        }

        function downvote(){

        }

        /**
         * Para el uso de la p�ginaci�n, definir en el controlador las siguientes variables:
         *
         vm.start = 0;
         vm.pagina = CommentVars.pagina;
         CommentVars.paginacion = 5; Cantidad de registros por p�gina
         vm.end = CommentVars.paginacion;


         En el HTML, en el ng-repeat agregar el siguiente filtro: limitTo:appCtrl.end:appCtrl.start;

         Agregar un bot�n de next:
         <button ng-click="appCtrl.next()">next</button>

         Agregar un bot�n de prev:
         <button ng-click="appCtrl.prev()">prev</button>

         Agregar un input para la p�gina:
         <input type="text" ng-keyup="appCtrl.goToPagina()" ng-model="appCtrl.pagina">

         */


        /**
         * @description: Ir a p�gina
         * @param pagina
         * @returns {*}
         * uso: agregar un m�todo
         vm.goToPagina = function () {
                vm.start= CommentService.goToPagina(vm.pagina).start;
            };
         */
        function goToPagina(pagina) {

            if (isNaN(pagina) || pagina < 1) {
                CommentVars.pagina = 1;
                return CommentVars;
            }

            if (pagina > CommentVars.paginas) {
                CommentVars.pagina = CommentVars.paginas;
                return CommentVars;
            }

            CommentVars.pagina = pagina - 1;
            CommentVars.start = CommentVars.pagina * CommentVars.paginacion;
            return CommentVars;

        }

        /**
         * @name next
         * @description Ir a pr�xima p�gina
         * @returns {*}
         * uso agregar un metodo
         vm.next = function () {
                vm.start = CommentService.next().start;
                vm.pagina = CommentVars.pagina;
            };
         */
        function next() {

            if (CommentVars.pagina + 1 > CommentVars.paginas) {
                return CommentVars;
            }
            CommentVars.start = (CommentVars.pagina * CommentVars.paginacion);
            CommentVars.pagina = CommentVars.pagina + 1;
            //CommentVars.end = CommentVars.start + CommentVars.paginacion;
            return CommentVars;
        }

        /**
         * @name previous
         * @description Ir a p�gina anterior
         * @returns {*}
         * uso, agregar un m�todo
         vm.prev = function () {
                vm.start= CommentService.prev().start;
                vm.pagina = CommentVars.pagina;
            };
         */
        function prev() {


            if (CommentVars.pagina - 2 < 0) {
                return CommentVars;
            }

            //CommentVars.end = CommentVars.start;
            CommentVars.start = (CommentVars.pagina - 2 ) * CommentVars.paginacion;
            CommentVars.pagina = CommentVars.pagina - 1;
            return CommentVars;
        }


    }

    CommentVars.$inject = ['$rootScope'];
    /**
     * @description Almacena variables temporales de Carritos
     * @param $rootScope
     * @constructor
     */
    function CommentVars($rootScope) {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;

        // Comentario Temporal
        this.comentarios = [];
        this.comentario = {};
        // Total de posts

        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

        // Transmite el aviso de actualizaci�n
        this.broadcast = function () {
            $rootScope.$broadcast("CommentVars")
        };

        // Recibe aviso de actualizaci�n
        this.listen = function (callback) {
            $rootScope.$on("CommentVars", callback)
        };

    }

})();