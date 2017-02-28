<?php

class Application_Model_Admin
{
    private $dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->dbTable = $dbTable;
        return $this->dbTable;
    }

    private function dbConnect()
    {
        return Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    public function fetchAll($userId)
    {
        $article = $this->setDbTable('Application_Model_DbTable_Article');
        $result  = $article->select()
                           ->from('article', array('id','title'))
                           ->where("user_id = ".$userId)
                           ->query()
                           ->fetchAll();

        $view = array();
        foreach ($result as $v) {
            $view[$v['id']] = array(
                'title' => $v['title'],
                'edit'  => "/admin/edit/?id=".$v['id'],
                'del'   => "/admin/del/?id=".$v['id']
                );
        }
        return $view;
    }

    // fetch column to add page
    public function fetchColumn()
    {
        $types = $this->setDbTable('Application_Model_DbTable_Types');
        $result = $types->fetchAll();
        return $result;
    }

    // add the article
    public function addArticle($data, $userId)
    {
        $requiredKeys = array('column', 'title', 'formaltext', 'link', 'tag');
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing requied key $key");
            }
        }

        //column
        $column = filter_var($data['column'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if (!$column) {
            throw new InvalidArgumentException('Column is invalid');
        }

        //title
        $title = trim($data['title']);
        if (empty($title)) {
            throw new InvalidArgumentException('Please fill the title');
        }
        $length = mb_strlen($title, 'UTF-8');
        if ($length > 64) {
            throw new InvalidArgumentException('Title is over range(64)!');
        }

        //link or formaltext
        $link = trim($data['link']);
        $formaltext = trim($data['formaltext']);

        if ($formaltext) {
            $length  = mb_strlen($formaltext, 'UTF-8');
            if ($length > 65534) {
                throw new InvalidArgumentException('Formaltext is over range(65535)!');
            }
            $isLink = 0;
        } else {
            $length  = mb_strlen($link, 'UTF-8');
            if ($length == 0) {
                throw new InvalidArgumentException("You should fill content");
            }
            if ($length > 2000) {
                throw new InvalidArgumentException('Link is over range(65535)!');
            }
            $link = filter_var($link, FILTER_VALIDATE_URL);
            if (!$link) {
                throw new InvalidArgumentException('Link is invalid');
            }
            $isLink = 1;
        }

        //tag
        if (!empty($data['tag'])) {
            $tagsExplode = explode(',', $data['tag']);
            if (count($tagsExplode) >= 10) {
                throw new InvalidArgumentException('Don\'t use over 10 tags');
            }
            $tags = array();
            foreach ($tagsExplode as $value) {
                if ($value) {
                    if (!in_array($value, $tags)) {
                        $length = mb_strlen($value, 'UTF-8');
                        if ($length > 32) {
                            throw new InvalidArgumentException('Some of tags is over range(32)!');
                        }
                        $tags[] = $value;
                    }
                }
            }
        } else {
            $tags = array();
        }

        // db start
        $db = $this->dbConnect();
        $db->beginTransaction();

        try {
            $db->insert('article', array(
                    'title'      => $title,
                    'formaltext' => $formaltext,
                    'column'     => $column,
                    'user_id'    => $userId,
                    'link'       => $link,
                    'is_link'    => $isLink
            ));
            $articleId = $db->lastInsertId();

            // If have tags
            if (!empty($tags)) {
                // Select all tags ,match the same
                $sql = "SELECT * FROM tag WHERE name in (?".str_repeat(',?', count($tags)-1).") ";
                
                $sameTags = $db->fetchAll($sql, $tags);

                $arrId   = array();
                $arrName = array();

                //Find the same tags id & name
                foreach ($sameTags as $value) {
                    $arrId[] = $value["id"];
                    $arrName[] = $value["name"];
                }

                //Tags:which is not in table
                $arrDiff = array_diff($tags, $arrName);
            } else {
                $arrDiff = array();
            }

            //If appear new tags
            if (!empty($arrDiff)) {
                // 3.insert new tag (match, and del the same tag)
                foreach ($arrDiff as $v) {
                    $db->insert('tag', array(
                        'name'      => $v,
                        'user_id'   => $userId));
                }
            }

            if (!empty($tags)) {
                // 4.Select diff tags id
                $sql = "SELECT * from tag WHERE name in (?".str_repeat(',?', count($tags)-1).")";
                $diffTags = $db->fetchAll($sql, $tags);

                $arrId   = array();
                //Find the same tags id & name
                foreach ($diffTags as $value) {
                    $arrId[] = $value["id"];
                }
                
                // 5.insert new tag & article (table tag_mid)
                foreach ($arrId as $v) {
                    $db->insert('tag_mid', array(
                        'tag_id'     => $v,
                        'article_id' => $articleId
                    ));
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function editArticle($data, $userId)
    {
    }
}
