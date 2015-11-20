<?php
/**
 * Class for creating indexes.
 */
class Indexer {

  protected $twig;
  protected $tz;

  function __construct(Twig_Environment $twig, DateTimeZone $tz) {
    $this->twig = $twig;
    $this->tz = $tz;
  }

  function create_indices($path, array $ancestors = array()) {
    $subdirs = array();
    $files = array();
    foreach (scandir($path) as $item) {
      $item_path = $path.'/'.$item;
      // Ignore parent directories and hidden files.
      if (preg_match('/^\./', $item) || (is_file($item_path) && !preg_match('/.+\.(kml|kmz)$/', $item))) {
        continue;
      }
      if (is_dir($item_path)) {
        $subdirs[] = $item;
      } else {
        $files[] = $item;
      }
    }

    // Add our directory listings first.
    foreach ($subdirs as $subdir) {
      // Add the subdir entry.
    }

    // Sort our files into groups and categorize their types
    $groups = array();
    $types = array();
    $other_files = array();
    foreach ($files as $item) {
      if (preg_match('/^([^\.]+)\.(.+)\.(kml|kmz)$/', $item, $m)) {
        if (!isset($groups[$m[1]])) {
          $groups[$m[1]] = array();
        }
        $groups[$m[1]][$m[2]]['filename'] = $item;
        $groups[$m[1]][$m[2]]['date'] = new DateTime('now', $this->tz);
        $groups[$m[1]][$m[2]]['date']->setTimestamp(filemtime($path.'/'.$item));
        $groups[$m[1]][$m[2]]['size'] = filesize($path.'/'.$item);
        $types[] = $m[2];
      } else {
        $other_files[] = $item;
      }
    }
    $types = array_unique($types);
    sort($types);
    foreach ($groups as $id => &$group) {
      foreach ($types as $type) {
        if (!isset($group[$type])) {
          $group[$type] = null;
        }
      }
      ksort($group);
    }

    // Add our headings.
    $headings = array_merge(array('Region'), $types, array("Date Updated"));

    file_put_contents($path."/index.html", $this->twig->render("index.html", array(
      'item_name' => basename($path),
      'subdirs' => $subdirs,
      'ancestors' => $ancestors,
      'groups' => $groups,
      'headings' => $headings,
      'other_files' => $other_files,
    )));

    $sub_ancestors = array_merge($ancestors, array(basename($path)));
    // Add indices for each group.
    // foreach ($groups as $group => $categories) {
    //   $this->add_group_index($path, $sub_ancestors, $group, $categories);
    // }

    // Recursively add indices for subdirectories.
    foreach ($subdirs as $subdir) {
      $this->create_indices($path.'/'.$subdir, $sub_ancestors);
    }
  }

}
