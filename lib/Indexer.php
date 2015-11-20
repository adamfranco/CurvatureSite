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
    $this->type_labels = array(
      'c_1000' => "Curvature ≥ 1000\nVery twisty",
      'c_300' => "Curvature ≥ 300\nModerately twisty",
      'c_1000.multicolor' => "Detected curves\ncolorized\n(Curvature ≥ 1000)",
      'c_300.multicolor' => "Detected curves\ncolorized\n(Curvature ≥ 300)",
      // 'surfaces' => 'Surfaces colorized',
    );
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
        if ($item != 'resources') {
          $subdirs[] = $item;
        }
      } else {
        $files[] = $item;
      }
    }

    // Sort our files into groups and categorize their types
    $groups = array();
    $types = array();
    $other_files = array();
    foreach ($files as $item) {
      $file = array();
      $file['filename'] = $item;
      $file['date'] = new DateTime('now', $this->tz);
      $file['date']->setTimestamp(filemtime($path.'/'.$item));
      $file['size'] = filesize($path.'/'.$item);

      if (preg_match('/^([^\.]+)\.(.+)\.(kml|kmz)$/', $item, $m)) {
        if (!isset($groups[$m[1]])) {
          $groups[$m[1]] = array();
        }
        $groups[$m[1]][$m[2]] = $file;
        $types[] = $m[2];
      } else {
        $other_files[] = $file;
      }
    }

    // Sort and label our types.
    $types = array_unique($types);
    $labeled_types = array();
    foreach ($this->type_labels as $type => $label) {
      if (in_array($type, $types)) {
        $labeled_types[$type] = $label;
        unset($types[array_search($type, $types)]);
      }
    }
    // Add any remaining types we don't have labels for.
    if (count($types)) {
      sort($types);
      foreach ($types as $type) {
        $labeled_types[$type] = ucwords($type);
      }
    }

    // Print out our groups of files.
    if (count($groups)) {
      // Add placeholder for groups that don't have a particular file
      // and sort the group according to our label-array.
      foreach ($groups as $id => $group) {
        $new_group = array();
        foreach ($labeled_types as $type => $label) {
          if (isset($group[$type])) {
            $new_group[$type] = $group[$type];
            $new_group[$type]['label'] = $label;
          } else {
            $new_group[$type] = null;
          }
        }
        $groups[$id] = $new_group;
      }
    }

    // Add our headings.
    $headings = array_merge(array('region' => 'Region'), $labeled_types, array('date' => 'Date Updated'));
    $breadcrumbs = array();
    $depth = count($ancestors);
    foreach ($ancestors as $i => $name) {
      $up_path = implode('/', array_fill(0, $depth - $i, '..')) . '/index.html';
      $breadcrumbs[$up_path] = $name;
    }
    if ($depth > 0) {
      $root_path = implode('/', array_fill(0, $depth, '..')).'/';
    } else {
      $root_path = '';
    }

    file_put_contents($path."/index.html", $this->twig->render("index.html", array(
      'item_name' => basename($path),
      'subdirs' => $subdirs,
      'groups' => $groups,
      'headings' => $headings,
      'other_files' => $other_files,
      'breadcrumbs' => $breadcrumbs,
      'root_path' => $root_path,
    )));

    $sub_ancestors = array_merge($ancestors, array(basename($path)));
    // Add indices for each group.
    foreach ($groups as $group => $group_files) {
      file_put_contents($path."/$group.html", $this->twig->render("index.html", array(
        'item_name' => basename($group),
        'subdirs' => array(),
        'group_mode' => 'single',
        'group' => $group,
        'group_files' => $group_files,
        'headings' => $headings,
        'other_files' => array(),
        'breadcrumbs' => array_merge($breadcrumbs,array('index.html' => basename($path))),
        'root_path' => $root_path,
      )));
    }

    // Recursively add indices for subdirectories.
    foreach ($subdirs as $subdir) {
      $this->create_indices($path.'/'.$subdir, $sub_ancestors);
    }
  }

}
