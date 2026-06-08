<?php
/**
 * BRXProd / nm- convention linter for Bricks pages & templates.
 *
 * Audits a Bricks element tree + global classes against the CLAUDE.md
 * "Definition of Done". Run it after any Bricks build/edit and only report the
 * work complete once it returns a clean PASS (errors = 0).
 *
 * USAGE: run the body via the Novamira `execute-php` ability (or paste into a
 * read-only eval). Set $POST_IDS to the page/template post ids to audit.
 * Returns a per-post report: errors[] (must fix), warnings[] (review), PASS bool.
 *
 * Rules checked (mechanical subset of CLAUDE.md):
 *  - every element has a `label` matching its BEM class (block -> NAME, element -> SEGMENT)
 *  - every element carries its own `nm-` BEM global class
 *  - framework classes (brxp-*/brxw-*) applied via _cssGlobalClasses, not plain _cssClasses
 *  - _cssCustom uses literal `.class` selectors, never %root%
 *  - no raw hex / px in _cssCustom (px -> warning, hex -> error)
 *  - list semantics: __list -> customTag ul/ol, __item -> li, __card -> article
 *  - <article> cards contain <header> + <footer> children
 *  - <section> landmarks carry aria-labelledby (warning)
 *  - images flagged for manual alt/source-order review (warning)
 */

$POST_IDS = [/* e.g. 25, 26 */];

$gc = get_option('bricks_global_classes', []);
$byId = []; $names = [];
foreach ((array)$gc as $c) { if (!empty($c['id'])) $byId[$c['id']] = $c; if (!empty($c['name'])) $names[$c['name']] = true; }

$expected_label = function($cn) {
  if (strpos($cn, '__') !== false) {
    $seg = substr($cn, strpos($cn, '__') + 2);
    if (strpos($seg, '--') !== false) $seg = substr($seg, 0, strpos($seg, '--'));
    return strtoupper(str_replace('-', ' ', $seg));
  }
  return strtoupper(str_replace('-', ' ', preg_replace('/^nm-/', '', $cn)));
};

$lint_class_css = function($cn) use ($byId) {
  $issues = []; $css = '';
  foreach ($byId as $c) { if (($c['name'] ?? '') === $cn) { $css = $c['settings']['_cssCustom'] ?? ''; break; } }
  if ($css === '') return $issues;
  if (strpos($css, '%root%') !== false) $issues[] = "E: class $cn uses %root% in _cssCustom (must be literal .$cn)";
  if (preg_match('/#[0-9a-fA-F]{3,8}\b/', $css)) $issues[] = "E: class $cn has a raw hex colour (use a brxp/brxw token)";
  if (preg_match_all('/(?<![\w.-])(\d*\.?\d+)px/', $css, $m)) {
    foreach ($m[1] as $v) { if ((float)$v !== 0.0) { $issues[] = "W: class $cn has a raw px value ({$v}px) (prefer a token)"; break; } }
  }
  return $issues;
};

$report = [];
foreach ($POST_IDS as $pid) {
  $E = get_post_meta($pid, '_bricks_page_content_2', true);
  if (!is_array($E)) { $report[$pid] = ['error' => 'no bricks content']; continue; }
  $childrenOf = [];
  foreach ($E as $el) { $childrenOf[$el['parent'] ?? 0][] = $el; }
  $errs = []; $warns = []; $seen = [];
  foreach ($E as $el) {
    $id = $el['id']; $name = $el['name']; $s = $el['settings'] ?? [];
    $where = "[$name#$id" . (isset($el['label']) ? " \"{$el['label']}\"" : '') . ']';
    $cn = '';
    foreach (($s['_cssGlobalClasses'] ?? []) as $cid) { $nm = $byId[$cid]['name'] ?? ''; if (strpos($nm, 'nm-') === 0) { $cn = $nm; break; } }
    if (empty($el['label'])) $errs[] = "$where missing label";
    if ($cn === '') { $errs[] = "$where has no nm- BEM global class"; }
    else {
      $lblBase = trim(preg_replace('/\s*[\(\[\{].*$/', '', $el['label'] ?? ''));
      $exp = $expected_label($cn);
      if ($lblBase !== $exp) $errs[] = "$where label \"$lblBase\" != expected \"$exp\" (from .$cn)";
      if (empty($seen[$cn])) { $seen[$cn] = true; foreach ($lint_class_css($cn) as $iss) { if ($iss[0] === 'E') $errs[] = $iss; else $warns[] = $iss; } }
      $ct = $s['customTag'] ?? '';
      if (preg_match('/__list$/', $cn) && !in_array($ct, ['ul', 'ol'], true)) $errs[] = "$where .$cn should render customTag ul/ol";
      if (preg_match('/__item$/', $cn) && $ct !== 'li') $errs[] = "$where .$cn should render customTag li";
      if (preg_match('/__card$/', $cn) && $ct !== 'article') $errs[] = "$where .$cn should render customTag article";
    }
    $plain = trim((string)($s['_cssClasses'] ?? ''));
    if ($plain !== '') foreach (preg_split('/\s+/', $plain) as $tok) {
      if ($tok !== '' && (strpos($tok, 'brxp-') === 0 || strpos($tok, 'brxw-') === 0 || isset($names[$tok])))
        $errs[] = "$where applies '$tok' via plain _cssClasses (use _cssGlobalClasses by id)";
    }
    if (($s['customTag'] ?? '') === 'article') {
      $kids = array_map(function($k){ return $k['settings']['customTag'] ?? ''; }, $childrenOf[$id] ?? []);
      if (!in_array('header', $kids, true) || !in_array('footer', $kids, true)) $errs[] = "$where <article> should contain <header> and <footer> children (got: " . implode(',', $kids) . ")";
    }
    if ($name === 'section') {
      $has = false; foreach (($s['_attributes'] ?? []) as $a) if (($a['name'] ?? '') === 'aria-labelledby') $has = true;
      if (!$has) $warns[] = "$where section has no aria-labelledby (add if it's a named landmark)";
    }
    if ($name === 'image') $warns[] = "$where image — verify alt='' if decorative and that it is LAST in source order";
  }
  $report[$pid] = ['errors' => $errs, 'warnings' => $warns, 'error_count' => count($errs), 'warning_count' => count($warns), 'PASS' => count($errs) === 0];
}
return $report;
