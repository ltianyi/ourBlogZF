<?php
return array(
    'article'=>array(
        array(
            'id'         => 1,
            'title'      => 'test article',
            'formaltext' => 'wojiushi zhengwen',
            'column'     => 1,
            'user_id'    => 1,
            'link'       => '',
            'is_link'    => 0
            ),
        array(
            'id'         => 2,
            'title'      => 'title',
            'formaltext' => '',
            'column'     => 1,
            'user_id'    => 1,
            'link'       => 'http://baidu.com',
            'is_link'    => 1
            )
    ),
    'tag'=>array(
        array('id'=>1, 'name'=>'php'),
        array('id'=>2, 'name'=>'java')
    ),
    'tag_mid'=>array(
        array('id'=>1, 'tag_id'=>1, 'article_id'=>1),
        array('id'=>2, 'tag_id'=>2, 'article_id'=>1)
    ));
