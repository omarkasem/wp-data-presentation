<?php
function wpdp_get_actors(){
    $actors = get_field('actor_filter','option');

    if(!$actors){
        return [];
    }

    $actor_list = [];

    foreach($actors as $actor){
        $filter = $actor['filter'];
        foreach($filter as $f){
            $value = ucfirst( strtolower( $f['text'] ) );
            $value = str_replace('militias', 'militia', $value);
            $value = str_replace('groups', 'group', $value);
            $actor_list[$f['actor_code'][0]] = $value;
        }
    }
    return $actor_list;
}