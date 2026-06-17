<?php
/**
 * BRXProd / nm- convention linter for Bricks pages, templates & components.
 *
 * Audits Bricks element trees + global classes against the CLAUDE.md
 * "Definition of Done". Run after any Bricks build/edit; report complete only
 * on a clean PASS (errors = 0). A clean PASS is necessary, not sufficient — it
 * proves no mechanical violation, not that you used Rails / components / the
 * right native element.
 *
 * USAGE: run the body via the Novamira `execute-php` ability. Set $POST_IDS to
 * the page/template ids to audit. Every Bricks Component body is audited
 * automatically (read from the bricks_components option).
 * Returns per-target: errors[] (must fix), warnings[] (review), PASS bool.
 *
 * Rules:
 *  - every element has a `label` matching its BEM class (block -> Name, element -> Segment; mixed case allowed, checked case-insensitively)
 *  - every element carries its own BEM global class (nm-, atom-, or cross-project prefix)
 *  - framework classes (brxp-*/brxw-*) applied via _cssGlobalClasses, not plain _cssClasses
 *  - _cssCustom uses literal `.class` selectors, never %root%; no raw hex (E) / px (W)
 *  - list semantics: __list -> ul/ol, __item -> li, __card -> article(+header+footer)
 *  - <section> landmarks carry aria-labelledby (W) AND carry brxp-rails (E, opt out via "(no-rails)")
 *  - no layout class uses max-width (on a non-text element) or margin:auto for width (E)
 *  - heading-first: no __eyebrow/__kicker/__tagline precedes a sibling heading (E)
 *  - flat array is depth-first (parent before children)
 *  - component candidate: structurally-identical subtree repeated >=2x (W)
 *  - component instances (`cid` pointers) are skipped on pages; their bodies are audited from bricks_components
 */

$POST_IDS = [/* e.g. 25, 26 */];

$gc = get_option('bricks_global_classes', []);
$byId = []; $names = [];
foreach ((array)$gc as $c) { if (!empty($c['id'])) $byId[$c['id']] = $c; if (!empty($c['name'])) $names[$c['name']] = true; }

$TEXT_ELEMENTS = ['heading', 'text-basic', 'text', 'post-title', 'post-excerpt'];

$expected_label = function($cn) {
  if (strpos($cn, '__') !== false) {
    $seg = substr($cn, strpos($cn, '__') + 2);
    if (strpos($seg, '--') !== false) $seg = substr($seg, 0, strpos($seg, '--'));
    return strtoupper(str_replace('-', ' ', $seg));
  }
  return strtoupper(str_replace('-', ' ', preg_replace('/^(nm|atom)-/', '', $cn)));
};

$css_of = function($cn) use ($byId) {
  foreach ($byId as $c) { if (($c['name'] ?? '') === $cn) return $c['settings']['_cssCustom'] ?? ''; }
  return '';
};

$lint_class_css = function($cn) use ($css_of) {
  $issues = []; $css = $css_of($cn);
  if ($css === '') return $issues;
  if (strpos($css, '%root%') !== false) $issues[] = "E: class $cn uses %root% in _cssCustom (must be literal .$cn)";
  $cssHexCheck = preg_replace('/\burl\s*\([^)]*\)/s', '', $css); // strip data URIs
  $cssHexCheck = preg_replace('/(-webkit-)?mask(?:-image|-mode|-repeat|-size|-position|-clip|-origin|-composite)?\s*:[^;]+;/i', '', $cssHexCheck); // mask hex = luminance value, not a design token
  if (preg_match('/#[0-9a-fA-F]{3,8}\b/', $cssHexCheck)) $issues[] = "E: class $cn has a raw hex colour (use a brxp/brxw token)";
  if (preg_match_all('/(?<![\w.-])(\d*\.?\d+)px/', $css, $m)) {
    foreach ($m[1] as $v) { if ((float)$v !== 0.0) { $issues[] = "W: class $cn has a raw px value ({$v}px) (prefer a token)"; break; } }
  }
  return $issues;
};

$lint_rails_css = function($cn, $elName) use ($css_of, $TEXT_ELEMENTS) {
  $issues = []; $css = $css_of($cn);
  if ($css === '') return $issues;
  if (preg_match('/margin(-inline)?\s*:\s*auto/i', $css) || preg_match('/margin\s*:\s*[^;]*\bauto\b/i', $css)) {
    $issues[] = "E: class $cn uses auto margins for layout width — centre with a Rails band, not margin:auto";
  }
  if (preg_match('/(?<!-)max-width\s*:/i', $css) && !in_array($elName, $TEXT_ELEMENTS, true)) {
    $issues[] = "E: class $cn (on <$elName>) sets max-width on a layout wrapper — width comes from a Rails band (max-width is only a text measure on a text element)";
  }
  return $issues;
};

$audit_tree = function($E) use ($byId, $names, $expected_label, $lint_class_css, $lint_rails_css) {
  $childrenOf = []; $byElId = [];
  foreach ($E as $el) { $childrenOf[$el['parent'] ?? 0][] = $el; $byElId[$el['id']] = $el; }
  $errs = []; $warns = []; $seen = [];
  $cnameOf = function($el) use ($byId) {
    foreach (($el['settings']['_cssGlobalClasses'] ?? []) as $cid) { $nm = $byId[$cid]['name'] ?? ''; if ($nm !== '' && strpos($nm, 'brxw-') !== 0 && strpos($nm, 'brxp-') !== 0) return $nm; }
    return '';
  };
  $seenInOrder = [];
  foreach ($E as $el) {
    $p = $el['parent'] ?? 0;
    if ($p !== 0 && $p !== '0' && empty($seenInOrder[$p])) $errs[] = "[{$el['name']}#{$el['id']}] flat-array order: parent '$p' appears AFTER this element — must be depth-first";
    $seenInOrder[$el['id']] = true;
  }
  foreach ($E as $el) {
    $id = $el['id']; $name = $el['name']; $s = $el['settings'] ?? [];
    if (!empty($el['cid'])) continue; // component instance pointer — body audited separately
    $where = "[$name#$id" . (isset($el['label']) ? " \"{$el['label']}\"" : '') . ']';
    $cn = $cnameOf($el);
    if (empty($el['label'])) $errs[] = "$where missing label";
    if ($cn === '') { $errs[] = "$where has no BEM global class (nm-, atom-, or cross-project)"; }
    else {
      $lblBase = trim(preg_replace('/\s*[\(\[\{].*$/', '', $el['label'] ?? ''));
      $exp = $expected_label($cn);
      if (strtoupper($lblBase) !== $exp) $errs[] = "$where label \"$lblBase\" != expected \"$exp\" (from .$cn, case-insensitive)";
      if (empty($seen[$cn])) { $seen[$cn] = true; foreach ($lint_class_css($cn) as $iss) { if ($iss[0] === 'E') $errs[] = $iss; else $warns[] = $iss; } }
      foreach ($lint_rails_css($cn, $name) as $iss) { if ($iss[0] === 'E') $errs[] = $iss; else $warns[] = $iss; }
      $ct = $s['customTag'] ?? '';
      if (preg_match('/__list$/', $cn) && !in_array($ct, ['ul', 'ol'], true)) $errs[] = "$where .$cn should render customTag ul/ol";
      if (preg_match('/__item$/', $cn) && $ct !== 'li') $errs[] = "$where .$cn should render customTag li";
      if (preg_match('/__card$/', $cn) && $ct !== 'article') $errs[] = "$where .$cn should render customTag article";
    }
    $plain = trim((string)($s['_cssClasses'] ?? ''));
    if ($plain !== '') foreach (preg_split('/\s+/', $plain) as $tok) {
      if ($tok !== '' && (strpos($tok, 'brxp-') === 0 || strpos($tok, 'brxw-') === 0 || isset($names[$tok]))) $errs[] = "$where applies '$tok' via plain _cssClasses (use _cssGlobalClasses by id)";
    }
    if (($s['customTag'] ?? '') === 'article') {
      $kids = array_map(function($k){ return $k['settings']['customTag'] ?? ''; }, $childrenOf[$id] ?? []);
      if (!in_array('header', $kids, true) || !in_array('footer', $kids, true)) $errs[] = "$where <article> should contain <header> and <footer> children (got: " . implode(',', $kids) . ")";
    }
    if ($name === 'section') {
      $has = false; foreach (($s['_attributes'] ?? []) as $a) if (($a['name'] ?? '') === 'aria-labelledby') $has = true;
      if (!$has) $warns[] = "$where section has no aria-labelledby (add if it's a named landmark)";
      $hasRails = false;
      foreach (($s['_cssGlobalClasses'] ?? []) as $cid) if (($byId[$cid]['name'] ?? '') === 'brxp-rails') $hasRails = true;
      if (!$hasRails && stripos($el['label'] ?? '', 'no-rails') === false) $errs[] = "$where section missing brxp-rails — outer width must come from a Rails band (or label it '(no-rails)')";
    }
    if ($name === 'image') $warns[] = "$where image — verify alt='' if decorative and that it is LAST in source order";
  }
  foreach ($childrenOf as $pid2 => $kids) {
    $firstHeadingIdx = null;
    foreach ($kids as $i => $k) { if ($k['name'] === 'heading') { $firstHeadingIdx = $i; break; } }
    if ($firstHeadingIdx === null) continue;
    foreach ($kids as $i => $k) {
      if ($i >= $firstHeadingIdx) break;
      $kcn = $cnameOf($k);
      if (in_array($k['name'], ['text-basic', 'text'], true) && preg_match('/__(eyebrow|kicker|tagline)\b/', $kcn)) $errs[] = "[{$k['name']}#{$k['id']}] .$kcn appears BEFORE its sibling heading — heading must come first; lift the eyebrow with CSS order";
    }
  }
  $sig = function($id) use (&$sig, $childrenOf, $byElId) {
    $el = $byElId[$id]; $kids = $childrenOf[$id] ?? [];
    $parts = []; foreach ($kids as $k) $parts[] = $sig($k['id']);
    $ct = $el['settings']['customTag'] ?? '';
    return [$el['name'] . ($ct ? ":$ct" : '') . '(' . implode(',', array_map(function($p){ return $p[0]; }, $parts)) . ')', 1 + array_sum(array_map(function($p){ return $p[1]; }, $parts))];
  };
  $tally = [];
  foreach ($E as $el) { if (!empty($el['cid'])) continue; list($sg, $sz) = $sig($el['id']); if ($sz >= 4) { $tally[$sg]['n'] = ($tally[$sg]['n'] ?? 0) + 1; $tally[$sg]['sz'] = $sz; } }
  foreach ($tally as $sg => $info) { if ($info['n'] >= 2) $warns[] = "component candidate: a {$info['sz']}-element subtree repeats {$info['n']}x — promote to a Bricks Component (or a query loop if CMS-driven)"; }
  return ['errors' => $errs, 'warnings' => $warns, 'error_count' => count($errs), 'warning_count' => count($warns), 'PASS' => count($errs) === 0];
};

$report = [];
$comps = get_option('bricks_components', []);
foreach ((array)$comps as $c) {
  if (empty($c['id']) || empty($c['elements'])) continue;
  $report['component:' . $c['id'] . ' (' . ($c['label'] ?? '') . ')'] = $audit_tree($c['elements']);
}
foreach ($POST_IDS as $pid) {
  $E = get_post_meta($pid, '_bricks_page_content_2', true);
  if (!is_array($E)) { $report[$pid] = ['error' => 'no bricks content']; continue; }
  $report[$pid] = $audit_tree($E);
}
return $report;
