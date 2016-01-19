<?php
/* TODO:
 * */


session_start();

// Token
$decoded_token = null;

if (file_exists('../../../includes/MyDBi.php')) {
    require_once '../../../includes/MyDBi.php';
    require_once '../../../includes/config.php';
} else {
    require_once 'MyDBi.php';
}

$data = file_get_contents("php://input");

// Decode data from js
$decoded = json_decode($data);


// Si la seguridad está activa
if ($jwt_enabled) {

    // Carga el jwt_helper
//    if (file_exists('../../../jwt_helper.php')) {
//        require_once '../../../jwt_helper.php';
//    } else {
//        require_once 'jwt_helper.php';
//    }


    // Las funciones en el if no necesitan usuario logged
    if (($decoded == null) && (($_GET["function"] != null) &&
            ($_GET["function"] == 'getPosts' ||
                $_GET["function"] == 'getTemas'))
    ) {
        $token = '';
    } else {
        checkSecurity();
    }

}


if ($decoded != null) {
    if ($decoded->function == 'createPost') {
        createPost($decoded->post);
    } else if ($decoded->function == 'createTema') {
        createTema($decoded->tema);
    } else if ($decoded->function == 'updatePost') {
        updatePost($decoded->post);
    } else if ($decoded->function == 'updateTema') {
        updateTema($decoded->tema);
    } else if ($decoded->function == 'removePost') {
        removePost($decoded->post_id);
    } else if ($decoded->function == 'removeTema') {
        removeTema($decoded->tema_id);
    }
} else {
    $function = $_GET["function"];
    if ($function == 'getPosts') {
        getPosts();
    } elseif ($function == 'getTemas') {
        getTemas($_GET["todos"]);
    }
}

/////// INSERT ////////
/**
 * @description Crea un post, su relación con uno o varios temas y sus fotos
 * @param $post
 */
function createPost($post)
{
    validateRol(1);
    $db = new MysqliDb();
    $db->startTransaction();
    $item_decoded = checkPosts(json_decode($post));

    $data = array(
        'usuario_id' => $item_decoded->usuario_id,
        'titulo' => $item_decoded->titulo,
        'detalle' => $item_decoded->detalle,
        'fecha' => substr($item_decoded->fecha, 0, 10),
        'status' => $item_decoded->status,
        'en_slider' => $item_decoded->en_slider,
        'vistas' => $item_decoded->vistas,
        'up_votes' => $item_decoded->up_votes,
        'down_votes' => $item_decoded->down_votes,
    );

    $result = $db->insert('posts', $data);
    if ($result > -1) {

        foreach ($item_decoded->fotos as $foto) {
            if (!createFotos($foto, $result, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }

        foreach ($item_decoded->temas as $tema) {
            if (!createPostTema($tema, $result, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Crea un proyecto y sus fotos
 * @param $proyecto_cambio
 */
function createTema($tema)
{
    validateRol(0);
    $db = new MysqliDb();
    $db->startTransaction();
    $item_decoded = checkTemas(json_decode($tema));

    $data = array(
        'nombre' => $item_decoded->nombre,
        'parent_id' => $item_decoded->parent_id,
        'status' => $item_decoded->status
    );

    $result = $db->insert('temas', $data);
    if ($result > -1) {

        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Crea una relación entre tema y post, solo puede ser creada internamente
 * @param $post_tema
 * @param $post_id
 * @param $db
 * @return bool
 */
function createPostTema($post_tema, $post_id, $db)
{

    $data = array(
        'post_id' => $post_id,
        'tema_id' => $post_tema->tema_id
    );

    $fot = $db->insert('posts_temas', $data);
    return ($fot > -1) ? true : false;
}

/**
 * @description Crea una foto para un proyecto determinado, main == 1 significa que la foto es la principal
 * @param $foto
 * @param $post_id
 * @param $db
 * @return bool
 */
function createFotos($foto, $post_id, $db)
{
    $data = array(
        'post_id' => $post_id,
        'nombre' => $foto->nombre,
        'main' => $foto->main,
        'carpeta' => $foto->carpeta
    );

    $fot = $db->insert('posts_fotos', $data);
    return ($fot > -1) ? true : false;
}

/////// UPDATE ////////

/**
 * @description Modifica un proyecto, sus fotos, precios y le asigna las comentarios
 * @param $proyect
 */
function updatePost($post)
{

    validateRol(0);

    $db = new MysqliDb();
    $db->startTransaction();
    $item_decoded = checkPosts(json_decode($post));

    $db->where('post_id', $item_decoded->post_id);
    $data = array(
        'usuario_id' => $item_decoded->usuario_id,
        'titulo' => $item_decoded->titulo,
        'detalle' => $item_decoded->detalle,
        'fecha' => substr($item_decoded->fecha, 0, 10),
        'status' => $item_decoded->status,
        'en_slider' => $item_decoded->en_slider,
        'vistas' => $item_decoded->vistas,
        'up_votes' => $item_decoded->up_votes,
        'down_votes' => $item_decoded->down_votes,
    );


    $result = $db->update('posts', $data);


    $db->where('post_id', $item_decoded->post_id);
    $db->delete('posts_fotos');

    if ($result) {

        foreach ($item_decoded->fotos as $foto) {
            if (!createFotos($foto, $item_decoded->post_id, $db)) {
                $db->rollback();
                echo json_encode(-1);
                return;
            }
        }


        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @param $proyecto_cambio
 */
function updateTema($tema)
{

    validateRol(0);

    $db = new MysqliDb();
    $db->startTransaction();
    $item_decoded = checkTemas(json_decode($tema));
    $db->where('tema_id', $item_decoded->tema_id);
    $data = array(
        'nombre' => $item_decoded->nombre,
        'parent_id' => $item_decoded->parent_id,
        'status' => $item_decoded->status
    );

    $result = $db->update('temas', $data);
    if ($result) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/////// REMOVE ////////

/**
 * @description Elimina un post, los temas a los que pertenece y sus fotos
 * @param $post_id
 */
function removePost($post_id)
{
    validateRol(0);
    $db = new MysqliDb();

    $db->where("post_id", $post_id);
    $results = $db->delete('posts');

    $db->where("post_id", $post_id);
    $db->delete('posts_fotos');

    $db->where("post_id", $post_id);
    $db->delete('posts_temas');


    if ($results) {

        echo json_encode(1);
    } else {
        echo json_encode(-1);

    }
}

/**
 * @description Genera una baja lógica del tema
 * @param $tema_id
 */
function removeTema($tema_id)
{
    validateRol(0);

    $db = new MysqliDb();
    $db->startTransaction();
    $db->where('tema_id', $tema_id);
    $data = array(
        'status' => 0
    );

    $result = $db->update('temas', $data);
    if ($result) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/////// GET ////////

/**
 * @descr Obtiene los posts con sus categorías y fotos
 */
function getPosts()
{
    $db = new MysqliDb();

//    $results = $db->get('proyectos');
    $results = $db->rawQuery('SELECT
    p.post_id,
    p.usuario_id,
    p.titulo,
    p.detalle,
    p.fecha,
    p.status,
    p.en_slider,
    p.vistas,
    p.up_votes,
    p.down_votes,
    t.tema_id,
    t.nombre,
    f.post_foto_id,
    f.main,
    f.nombre nombreFoto,
    f.carpeta
FROM
    posts p
        LEFT JOIN
    posts_temas pt ON p.post_id = pt.post_id
        LEFT JOIN
    temas t ON (pt.tema_id = t.tema_id AND t.status = 1)
        LEFT JOIN
    posts_fotos f ON f.post_id = p.post_id');


    $final = array();
    foreach ($results as $row) {

        if (!isset($final[$row["post_id"]])) {
            $final[$row["post_id"]] = array(
                'post_id' => $row["post_id"],
                'usuario_id' => $row["usuario_id"],
                'titulo' => $row["titulo"],
                'detalle' => $row["detalle"],
                'fecha' => $row["fecha"],
                'status' => $row["status"],
                'en_slider' => $row["en_slider"],
                'vistas' => $row["vistas"],
                'up_votes' => $row["up_votes"],
                'down_votes' => $row["down_votes"],
                'fotos' => array(),
                'temas' => array()
            );
        }


        $have_fot = false;
        if ($row["post_foto_id"] !== null) {

            if (sizeof($final[$row['post_id']]['fotos']) > 0) {
                foreach ($final[$row['post_id']]['fotos'] as $cat) {
                    if ($cat['post_foto_id'] == $row["post_foto_id"]) {
                        $have_fot = true;
                    }
                }
            } else {
                $final[$row['post_id']]['fotos'][] = array(
                    'post_foto_id' => $row['proyecto_foto_id'],
                    'nombre' => $row['nombreFoto'],
                    'main' => $row['main'],
                    'carpeta' => $row['carpeta']
                );

                $have_fot = true;
            }

            if (!$have_fot) {
                array_push($final[$row['post_id']]['fotos'], array(
                    'proyecto_foto_id' => $row['proyecto_foto_id'],
                    'nombre' => $row['nombreFoto'],
                    'main' => $row['main'],
                    'carpeta' => $row['carpeta']
                ));
            }
        }

        $have_tem = false;
        if ($row["tema_id"] !== null) {

            if (sizeof($final[$row['post_id']]['temas']) > 0) {
                foreach ($final[$row['post_id']]['temas'] as $cat) {
                    if ($cat['tema_id'] == $row["tema_id"]) {
                        $have_tem = true;
                    }
                }
            } else {
                $final[$row['post_id']]['temas'][] = array(
                    'tema_id' => $row['tema_id'],
                    'nombre' => $row['nombre']
                );

                $have_tem = true;
            }

            if (!$have_tem) {
                array_push($final[$row['post_id']]['temas'], array(
                    'tema_id' => $row['tema_id'],
                    'nombre' => $row['nombre']
                ));
            }
        }


    }
    echo json_encode(array_values($final));
}


/**
 * @descr Obtiene los cambios
 */
function getTemas($todos)
{
    $db = new MysqliDb();

    $where = '';
    if ($todos == 'true' || $todos == true) {
        $where = ' where t.status=1 ';
    }

    $results = $db->rawQuery('SELECT
    t.tema_id,
    t.nombre,
    t.parent_id,
    (SELECT
            t1.nombre
        FROM
            temas t1
        WHERE
            t.tema_id = t1.tema_id) parentNombre
FROM
    temas t ' . $where);


    echo json_encode($results);
}

//// VALIDACIONES ////

/**
 * @description Verifica todos los campos de proyecto para que existan
 * @param $proyecto
 * @return mixed
 */
function checkPosts($item)
{
    $now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

    $item->usuario_id = (!array_key_exists("usuario_id", $item)) ? 0 : $item->usuario_id;
    $item->titulo = (!array_key_exists("titulo", $item)) ? '' : $item->titulo;
    $item->detalle = (!array_key_exists("detalle", $item)) ? '' : $item->detalle;
    $item->fecha = (!array_key_exists("fecha", $item)) ? $now->format('Y-m-d H:i:s') : $item->fecha;
    $item->status = (!array_key_exists("status", $item)) ? 0 : $item->status;
    $item->en_slider = (!array_key_exists("en_slider", $item)) ? 0 : $item->en_slider;
    $item->vistas = (!array_key_exists("vistas", $item)) ? 0 : $item->vistas;
    $item->up_votes = (!array_key_exists("up_votes", $item)) ? 0 : $item->up_votes;
    $item->down_votes = (!array_key_exists("down_votes", $item)) ? 0 : $item->down_votes;
    $item->fotos = (!array_key_exists("fotos", $item)) ? array() : checkFotos($item->fotos);
    $item->temas = (!array_key_exists("temas", $item)) ? array() : checkTemas($item->temas);

    return $item;
}

/**
 * @description Verifica todos los campos de proyecto para que existan
 * @param $proyecto
 * @return mixed
 */
function checkTemas($item)
{
    $now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

    $item->nombre = (!array_key_exists("nombre", $item)) ? '' : $item->nombre;
    $item->parent_id = (!array_key_exists("parent_id", $item)) ? 0 : $item->parent_id;
    $item->status = (!array_key_exists("status", $item)) ? 0 : $item->status;

    return $item;
}

/**
 * @description Verifica todos los campos de fotos para que existan
 * @param $fotos
 * @return mixed
 */
function checkFotos($items)
{
    foreach ($items as $item) {
        $item->post_id = (!array_key_exists("proyecto_id", $item)) ? 0 : $item->proyecto_id;
        $item->nombre = (!array_key_exists("nombre", $item)) ? '' : $item->nombre;
        $item->main = (!array_key_exists("main", $item)) ? 0 : $item->main;
        $item->carpeta = (!array_key_exists("carpeta", $item)) ? 0 : $item->carpeta;
    }
    return $items;
}

/**
 * @description Verifica todos los campos de comentario del proyecto para que existan
 * @param $comentarios
 * @return mixed
 */
function checkPostsTemas($items)
{
    foreach ($items as $item) {
        $item->post_id = (!array_key_exists("post_id", $item)) ? 0 : $item->post_id;
        $item->tema_id = (!array_key_exists("tema_id", $item)) ? 0 : $item->tema_id;
    }

    return $items;
}


/**
 * @description Crea la relación entre un post y una categoría
 * @param $comentario
 * @param $post_id
 * @param $db
 * @return bool
 */
function createComentarios($comentario, $post_id, $db)
{
    $data = array(
        'post_id' => $post_id,
        'titulo' => $comentario->titulo,
        'detalles' => $comentario->detalles,
        'parent_id' => $comentario->parent_id,
        'creador_id' => $comentario->creador_id,
        'votos_up' => $comentario->votos_up,
        'votos_down' => $comentario->votos_down,
        'fecha' => $comentario->fecha
    );

    $cat = $db->insert('posts_comentarios', $data);
    return ($cat > -1) ? true : false;
}

/**
 * @description Modifica una comentario
 * @param $comentario
 */
function updateComentario($comentario)
{
    $db = new MysqliDb();
    $db->startTransaction();
    $comentario_decoded = checkComentario(json_decode($comentario));
    $db->where('comentario_id', $comentario_decoded->comentario_id);
    $data = array(
        'post_id' => $comentario_decoded->post_id,
        'titulo' => $comentario_decoded->titulo,
        'detalles' => $comentario_decoded->detalles,
        'parent_id' => $comentario_decoded->parent_id,
        'creador_id' => $comentario_decoded->creador_id,
        'votos_up' => $comentario_decoded->votos_up,
        'votos_down' => $comentario_decoded->votos_down,
        'fecha' => $comentario_decoded->fecha
    );

    $result = $db->update('posts_comentarios', $data);
    if ($result) {
        $db->commit();
        echo json_encode($result);
    } else {
        $db->rollback();
        echo json_encode(-1);
    }
}

/**
 * @description Elimina una comentario
 * @param $comentario_id
 */
function removeComentario($comentario_id)
{
    validateRol(0);
    $db = new MysqliDb();

    $db->where("comentario_id", $comentario_id);
    $results = $db->delete('posts_comentarios');

    if ($results) {

        echo json_encode(1);
    } else {
        echo json_encode(-1);

    }
}


/**
 * @descr Obtiene las comentarios
 */
function getComentarios($post_id)
{
    $db = new MysqliDb();
    $results = $db->rawQuery('SELECT
    c.post_comentario_id,
    c.post_id,
    c.titulo,
    c.detalles,
    c.parent_id,
    c.creador_id,
    c.votos_up,
    c.votos_down,
    c.fecha,
    u.nombre,
    u.apellido
FROM
    posts_comentarios c
        LEFT JOIN
    usuarios u ON u.usuario_id = c.creador_id
WHERE
    c.post_id = ' . $post_id . '
    order by c.post_comentario_id;');


    echo json_encode($results);
}


/**
 * @description Verifica todos los campos de comentario del post para que existan
 * @param $comentarios
 * @return mixed
 */
function checkComentarios($comentarios)
{
    foreach ($comentarios as $comentario) {
        $comentario->post_id = (!array_key_exists("post_id", $comentario)) ? 0 : $comentario->post_id;
        $comentario->titulo = (!array_key_exists("titulo", $comentario)) ? 0 : $comentario->titulo;
        $comentario->detalles = (!array_key_exists("detalles", $comentario)) ? 0 : $comentario->detalles;
        $comentario->parent_id = (!array_key_exists("parent_id", $comentario)) ? 0 : $comentario->parent_id;
        $comentario->creador_id = (!array_key_exists("creador_id", $comentario)) ? 0 : $comentario->creador_id;
        $comentario->votos_up = (!array_key_exists("votos_up", $comentario)) ? 0 : $comentario->votos_up;
        $comentario->votos_down = (!array_key_exists("votos_down", $comentario)) ? 0 : $comentario->votos_down;
        $comentario->fecha = (!array_key_exists("fecha", $comentario)) ? 0 : $comentario->fecha;
    }

    return $comentarios;
}