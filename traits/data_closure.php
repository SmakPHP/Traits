<?php

// Основное пространство имен
namespace traits;

/**
 * Класс для построения дерева элементов
 *
 * Дополнительно:
 * https://habrahabr.ru/post/193166/
 * https://gist.github.com/loonies/1380975
 *
 * Пример использования:

  -- Элементы
  DROP TABLE IF EXISTS `cs_table`;
  CREATE TABLE `cs_table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `vendor` varchar(255) NOT NULL,
    `image` varchar(255) NOT NULL,
    `description` text NOT NULL,
    PRIMARY KEY (`id`)
  );

  -- Дерево элементов
  DROP TABLE IF EXISTS `cs_tree`;
  CREATE TABLE `cs_tree` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ancestor` int(11) NOT NULL,
    `descendant` int(11) NOT NULL,
    `lvl` int(11) NOT NULL,
    PRIMARY KEY (`id`)
  );

  // Подключение библиотеки
  require_once "traits/register.php";
  // Инициализация класса для работы с древовидными данными
  $db = new traits\data_base("test", "password");

  // Шаблон данных для добавления
  $data = array(
    "name" => "Name",
    "vendor" => "Vendor name",
    "image" => "Path to image",
    "description" => "Sample description",
  );

  // Добавляем данные
  $db->add($data);
  $db->add($data, 1);
  $db->add($data, 2);
  $db->add($data, 2);
  $db->add($data, 3);

  // Просмотр дерева
  $tree = $db->assembly($db->get_children(0));
  // Скриншот результата
  // https://s.mail.ru/AgKn/AL4FpuC1h

 */

/**
 * Class data_closure
 */
class data_closure extends data_base {

  /**
   * Даблица с элементами
   * @var string
   */
  public $table = "cs_table";

  /**
   * Таблица дерева элементов
   * @var string
   */
  public $closure_table = "cs_tree";

  /**
   * Добавление узла (в качестве последнего дочернего элемента)
   * @param	array	$data Данные для добавления
   * @param	mixed	$target_id Идентификатор узла назначения
   * @return mixed
   */
  public function add($data, $target_id = 0) {
    $target_id = $this->safe($target_id, "int");
    $target = $this->query(
      "SELECT `id` FROM `{$this->table}` WHERE `id` = {$target_id}"
    );
    $target_id = ($target->num_rows) ? $target->row["id"] : 0;
    $names = "`".implode("`,`", array_map(array($this, "safe"), array_keys($data)))."`";
    $values = "\"".implode("\",\"", array_map(array($this, "safe"), $data))."\"";
    $this->query(
      "INSERT INTO `{$this->table}` (".$names.") VALUES (".$values.")"
    );
    if ($this->insert_id) {
      $this->query(
        "INSERT INTO `{$this->closure_table}` (`ancestor`, `descendant`, `lvl`) ".
        "SELECT `ancestor`, {$this->insert_id}, `lvl`+1 FROM `{$this->closure_table}` ".
        "WHERE `descendant` = {$target_id} UNION ALL ".
        "SELECT {$this->insert_id}, {$this->insert_id}, 0"
      );
      return $this->insert_id;
    }
    return false;
  }

  /**
   * Получить родителя текущего узла
   * @param	int $node_id Идентификатор узла
   * @param	mixed	$level Уровень вложенности
   * @return mixed
   */
  public function get_parent($node_id, $level = 0)	{
    $sql =
      "SELECT `a` FROM `{$this->table}` AS `t` ".
      "JOIN `{$this->closure_table}` AS `c` ON `t`.`id` = `c`.`ancestor` ".
      "WHERE `c`.`descendant` = {$node_id} AND `c`.`ancestor` <> {$node_id}";
    if ($level)	{
      $level = $this->safe($level, "int");
      $sql .= " AND `c1`.`lvl` <= {$level}";
    }
    $result = $this->query($sql." ORDER BY `t`.`id` ASC");
    return $result->num_rows ? $result->rows : false;
  }

  /**
   * Выборка дочерних узлов
   * @param	mixed	$node_id Идентификатор узла
   * @param	boolean	$self Включить текущий узел
   * @param	mixed	$level Уровень вложенности
   * @return mixed array
   */
  public function get_children($node_id = 0, $self = false, $level = 0)	{
    $node_id = $node_id ? $this->safe($node_id, "int") : 1;
    $param = "`t`.*, `c2`.`ancestor` AS `parent`, `c1`.`lvl` AS `level`";
    $sql =
      "SELECT {$param} FROM `{$this->closure_table}` AS `c1` ".
      "JOIN `{$this->table}` AS `t` ON `t`.`id` = `c1`.`descendant` ".
      "LEFT JOIN `{$this->closure_table}` AS `c2` ".
      "ON `c2`.`lvl` = 1 AND `c2`.`descendant` = `c1`.`descendant` ".
      "WHERE `c1`.`ancestor` = {$node_id}";
    if (!$self)	$sql .= " AND `c1`.`descendant` <> {$node_id}";
    if ($level)	{
      $level = $this->safe($level, "int");
      $sql .= " AND `c1`.`lvl` = {$level}";
    }
    $result = $this->query($sql." ORDER BY `t`.`id` ASC");
    return $result->num_rows ? $result->rows : false;
  }

  /**
   * Удаление узла
   * @param	int	$node_id Идентификатор узла
   * @return void
   */
  public function delete($node_id) {
    $query = $this->query(
      "SELECT `descendant` FROM `{$this->closure_table}` WHERE `ancestor` = {$node_id}"
    );
    if ($query->num_rows) {
      $descendant = array_column($query->rows, "descendant");
      $query = $this->query(
        "SELECT `id`, `descendant` FROM `{$this->closure_table}` ".
        "WHERE `descendant` IN (".implode(",", $descendant).")"
      );
      if ($query->num_rows) {
        $id = array_column($query->rows, "id");
        $descendant = array_column($query->rows, "descendant");
        $this->query(
          "DELETE FROM `{$this->table}` WHERE `id` IN (".implode(",", $descendant).")"
        );
        $this->query(
          "DELETE FROM `{$this->closure_table}` WHERE `id` IN (".implode(",", $id).")"
        );
      }
    }
  }

  /**
   * Перемещение узла со всеми дочерними в другой
   * @param	int	$node_id Идентификатор узла для перемещения
   * @param	int	$target_id Идентификатор узла назначения
   * @return	void
   */
  public function move($node_id, $target_id) {
    $this->query(
      "DELETE `a` FROM `{$this->closure_table}` AS `a` ".
      "JOIN `{$this->closure_table}` AS `d` ON `a`.`descendant` = `d`.`descendant` ".
      "LEFT JOIN `{$this->closure_table}` AS `x` ".
      "ON `x`.`ancestor` = `d`.`ancestor` AND `x`.`descendant` = `a`.`ancestor` ".
      "WHERE `d`.`ancestor` = {$node_id} AND `x`.`ancestor` IS NULL"
    );
    $this->query(
      "INSERT INTO `{$this->closure_table}` (`ancestor`, `descendant`, `lvl`) ".
      "SELECT `a`.`ancestor`, `b`.`descendant`, `a`.`lvl` + `b`.`lvl` + 1 ".
      "FROM `{$this->closure_table}` AS `a` JOIN `{$this->closure_table}` AS `b` ".
      "WHERE `b`.`ancestor` = {$node_id} AND `a`.`descendant` = {$target_id}"
    );
  }

  /**
   * Сборка дерева
   * @param	array $nodes
   * @param	string $key
   * @return	mixed	array
   */
  public function assembly($nodes, $key = "parent") {
    if (count($nodes) < 1) return false;
    $trees = array();	$stack = array();
    foreach ($nodes as $node) {
      // Number of stack items
      $counter = count($stack);
      // Check if we"re dealing with different levels
      while ($counter > 0 && $stack[$counter - 1][$key] >= $node[$key]) {
        array_pop($stack);
        $counter--;
      }
      // Stack not empty (we are inspecting the children)
      if ($counter > 0) {
        $idx = $counter - 1;
        if (!isset($stack[$idx]["children"]))	{
          $stack[$idx]["children"] = array();
        }
        $i = count($stack[$idx]["children"]);
        $stack[$idx]["children"][$i] = $node;
        $stack[] = &$stack[$idx]["children"][$i];
      }	else {
        $i = count($trees);
        $trees[$i] = $node;
        $stack[] = &$trees[$i];
      }
    }
    return $trees;
  }

}