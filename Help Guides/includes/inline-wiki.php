<?php
/**
 * Wiki Inline Targets (AJAX-based)
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * Admin CSS for the dashicon marker + popover.
 * (You can keep this in admin_head if you prefer.)
 */
add_action('admin_head', function () {
	if ( ! is_admin() ) return;
	echo '<style>
		/* The clickable marker we insert AFTER the target element */
		.wiki-inline-help-icon{
			display: inline-block;
			margin-left: 6px;
			vertical-align: middle;
			line-height: 1;
			cursor: pointer;
			position: relative;
		}
        *:has(> .wiki-inline-help-icon) {
            display: contents;
        }
		.wiki-inline-help-icon::after{
			font-family: dashicons;
			content: "\\f348"; /* dashicons-info */
			display: inline-block;
			font-size: 1.5rem;
			vertical-align: middle;
			color: #ff00c6;
		}
		.wiki-inline-help-icon:hover{ opacity: 1; }

		/* Simple popover */
		.wiki-inline-help-popover{
			position: absolute;
			z-index: 999999;
			min-width: 280px;
			max-width: 520px;
			background: #fff;
			border: 1px solid rgba(0,0,0,.15);
			border-radius: 6px;
			box-shadow: 0 10px 25px rgba(0,0,0,.15);
			padding: 12px 12px 10px;
		}
		.wiki-inline-help-popover .wiki-inline-help-popover__head{
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 8px;
		}
		.wiki-inline-help-popover .wiki-inline-help-popover__title{
			font-size: 13px;
			font-weight: 600;
			margin: 0;
		}
		.wiki-inline-help-popover .wiki-inline-help-popover__close{
			border: 0;
			background: transparent;
			cursor: pointer;
			padding: 0;
			line-height: 1;
			opacity: .75;
		}
        .wiki-inline-help-popover__body{
        	font-size: 13px;
        	max-height: 320px;     /* adjust to taste */
        	overflow-y: auto;
        	padding-right: 4px;   /* avoids scrollbar overlaying text */
        }

		.wiki-inline-help-popover .wiki-inline-help-popover__close:hover{ opacity: 1; }
		.wiki-inline-help-popover .wiki-inline-help-popover__body{
			font-size: 13px;
		}
		.wiki-inline-help-popover .wiki-inline-help-popover__body p{
			margin: 0 0 10px;
		}
		.wiki-inline-help-popover .wiki-inline-help-popover__body p:last-child{
			margin-bottom: 0;
		}
	</style>';
});

/**
 * Load a tiny inline script on ALL wp-admin pages.
 */
add_action('admin_enqueue_scripts', function () {
	// A handle is required to attach inline JS
	wp_register_script('wiki-inline-targets', '', [], '1.1', true);
	wp_enqueue_script('wiki-inline-targets');

	wp_localize_script('wiki-inline-targets', 'WikiInline', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('wiki_inline_targets'),
	]);

	$js = <<<'JS'
(function(){
	// super visible proof we are executing
	

	var popoverEl = null;

	function closePopover() {
		if (popoverEl && popoverEl.parentNode) {
			popoverEl.parentNode.removeChild(popoverEl);
		}
		popoverEl = null;
	}

	function buildPopover(title, html) {
		var wrap = document.createElement('div');
		wrap.className = 'wiki-inline-help-popover';
		wrap.innerHTML =
			'<div class="wiki-inline-help-popover__head">' +
				'<p class="wiki-inline-help-popover__title"></p>' +
				'<button type="button" class="wiki-inline-help-popover__close" aria-label="Close">' +
					'<span class="dashicons dashicons-no-alt"></span>' +
				'</button>' +
			'</div>' +
			'<div class="wiki-inline-help-popover__body"></div>';

		wrap.querySelector('.wiki-inline-help-popover__title').textContent = title || 'Help';
		wrap.querySelector('.wiki-inline-help-popover__body').innerHTML = html || '';
		wrap.querySelector('.wiki-inline-help-popover__close').addEventListener('click', function(e){
			e.preventDefault();
			closePopover();
		});

		return wrap;
	}

	function positionPopover(anchor, pop) {
		var r = anchor.getBoundingClientRect();
		var top  = r.bottom + window.scrollY + 6;
		var left = r.left + window.scrollX;

		// keep on screen (basic)
		var maxLeft = window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 12;
		if (left > maxLeft) left = Math.max(window.scrollX + 12, maxLeft);

		pop.style.top  = top + 'px';
		pop.style.left = left + 'px';
	}

	function fetchWikiContent(wikiId, cb) {
		var form = new FormData();
		form.append("action", "wiki_inline_get_post");
		form.append("nonce", WikiInline.nonce);
		form.append("id", wikiId);

		fetch(WikiInline.ajax_url, {
			method: "POST",
			credentials: "same-origin",
			body: form
		})
		.then(function(r){ return r.json(); })
		.then(function(resp){
			if (!resp || !resp.success) return cb(new Error("Not success"));
			cb(null, resp.data);
		})
		.catch(function(err){
			cb(err);
		});
	}

	function run() {
		var url = window.location.href;
		console.log("[Wiki Inline] Current URL:", url);

		var form = new FormData();
		form.append("action", "wiki_inline_targets");
		form.append("nonce", WikiInline.nonce);
		form.append("url", url);

		fetch(WikiInline.ajax_url, {
			method: "POST",
			credentials: "same-origin",
			body: form
		})
		.then(function(r){ return r.json(); })
		.then(function(resp){
			console.log("[Wiki Inline] AJAX response:", resp);

			if (!resp || !resp.success) {
				console.log("[Wiki Inline] Not success.");
				return;
			}

			console.log("[Wiki Inline] Wiki posts checked:", resp.data.checked_posts);
			console.log("[Wiki Inline] Matched items:", resp.data.matches.length);

			resp.data.matches.forEach(function(item){
				console.log("[Wiki Inline] Match wiki_id:", item.id, "title:", item.title);

				item.targets.forEach(function(t){
					console.log("[Wiki Inline]  selectors:", t.selectors, "rules:", t.rules, "position:", t.position);

					var pos = parseInt(t.position, 10);
					if (!pos || pos < 1) pos = 1;

					// Find *all* matches and choose the Nth (1-based)
					var nodes = document.querySelectorAll(t.selectors);
					if (!nodes || !nodes.length) {
						console.log("[Wiki Inline]  no elements found for:", t.selectors);
						return;
					}
					if (nodes.length < pos) {
						console.log("[Wiki Inline]  position", pos, "out of range for selector:", t.selectors, "found:", nodes.length);
						return;
					}

					var el = nodes[pos - 1];

					// Insert a marker AFTER the target element (sibling), not inside it
					var icon = document.createElement('span');
					icon.className = 'wiki-inline-help-icon';
					icon.setAttribute('data-wiki-id', item.id);
					icon.setAttribute('data-wiki-url', item.url);
					icon.setAttribute('title', item.title || 'Help');

					// avoid duplicate markers if script runs twice
					var next = el.nextSibling;
					if (next && next.nodeType === 1 && next.classList.contains('wiki-inline-help-icon') && next.getAttribute('data-wiki-id') == String(item.id)) {
						console.log("[Wiki Inline]  marker already exists for:", el);
						return;
					}

					el.insertAdjacentElement('afterend', icon);

					console.log("[Wiki Inline]  marker inserted after:", el);
				});
			});

			if (!resp.data.matches.length) {
				console.log("[Wiki Inline] No matches applied on this page.");
			}
		})
		.catch(function(err){
			console.error("[Wiki Inline] AJAX error:", err);
		});
	}

	// Click handling (delegated)
	document.addEventListener('click', function(e){
		var icon = e.target.closest && e.target.closest('.wiki-inline-help-icon');
		if (!icon) {
			// click outside closes
			if (popoverEl) closePopover();
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		var wikiId = icon.getAttribute('data-wiki-id');
		if (!wikiId) return;

		// toggle
		if (popoverEl) {
			closePopover();
			// if you clicked the same icon, it should just close
			// (re-open below if you want always open)
		}

		// show loading popover immediately
		popoverEl = buildPopover(icon.getAttribute('title') || 'Help', '<p>Loading…</p>');
		document.body.appendChild(popoverEl);
		positionPopover(icon, popoverEl);

		fetchWikiContent(wikiId, function(err, data){
			if (!popoverEl) return;

			if (err || !data) {
				popoverEl.querySelector('.wiki-inline-help-popover__body').innerHTML = '<p>Could not load help content.</p>';
				return;
			}

			popoverEl.querySelector('.wiki-inline-help-popover__title').textContent = data.title || 'Help';
			popoverEl.querySelector('.wiki-inline-help-popover__body').innerHTML = data.html || '';
			positionPopover(icon, popoverEl);
		});
	});

	// keep popover aligned on scroll/resize
	window.addEventListener('scroll', function(){
		// best-effort: close on scroll so it doesn't drift
		if (popoverEl) closePopover();
	}, { passive: true });
	window.addEventListener('resize', function(){
		if (popoverEl) closePopover();
	});

	// wp-admin DOM timing
	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", run);
	} else {
		run();
	}
})();
JS;

	wp_add_inline_script('wiki-inline-targets', $js, 'after');
});

/**
 * AJAX handler (match targets for current URL)
 */
add_action('wp_ajax_wiki_inline_targets', function () {
	check_ajax_referer('wiki_inline_targets', 'nonce');

	$url = isset($_POST['url']) ? (string) wp_unslash($_POST['url']) : '';
	// normalize just in case
	$url = trim($url);

	$wiki_posts = get_posts([
		'post_type'      => 'wiki',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'fields'         => 'all',
	]);

	$checked = 0;
	$matches = [];

	foreach ($wiki_posts as $p) {
		$checked++;

		$targets = get_field('context_targets', $p->ID); // your repeater
		if (empty($targets) || !is_array($targets)) {
			continue;
		}

		$matched_targets = [];

		foreach ($targets as $t) {
			$selectors = isset($t['css_selectors']) ? trim((string)$t['css_selectors']) : '';
			$position  = isset($t['css_position']) ? trim((string)$t['css_position']) : ''; // NEW
			$rules     = isset($t['url_rules']) && is_array($t['url_rules']) ? $t['url_rules'] : [];

			if ($selectors === '') continue;

			$norm_rules = [];
			foreach ($rules as $r) {
				$mode    = isset($r['mode']) ? strtolower(trim((string)$r['mode'])) : 'include';
				$pattern = isset($r['pattern']) ? trim((string)$r['pattern']) : '';
				if ($pattern === '') continue;

				$norm_rules[] = [
					'mode'    => ($mode === 'exclude') ? 'exclude' : 'include',
					'pattern' => $pattern,
				];
			}

			// if no rules, don’t match anything (your call; easiest to reason about)
			if (empty($norm_rules)) {
				continue;
			}

			if (wiki_inline_url_rules_match($url, $norm_rules)) {
				$matched_targets[] = [
					'selectors' => $selectors,
					'position'  => $position, // NEW (1-based nth match)
					'rules'     => $norm_rules,
				];
			}
		}

		if (!empty($matched_targets)) {
			$matches[] = [
				'id'      => (int) $p->ID,
				'title'   => (string) $p->post_title,
				'url'     => (string) admin_url('admin.php?page=editor-wiki-browser&wiki_id=' . (int) $p->ID),
				'targets' => $matched_targets,
			];
		}
	}

	wp_send_json_success([
		'checked_posts' => $checked,
		'current_url'   => $url,
		'matches'       => $matches,
	]);
});

/**
 * AJAX handler (load wiki post content for popover)
 */
add_action('wp_ajax_wiki_inline_get_post', function () {
	check_ajax_referer('wiki_inline_targets', 'nonce');

	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
	if ( ! $id ) {
		wp_send_json_error(['message' => 'Missing id']);
	}

	$post = get_post($id);
	if ( ! $post || $post->post_type !== 'wiki' || $post->post_status !== 'publish' ) {
		wp_send_json_error(['message' => 'Not found']);
	}

	// Render content like the frontend would, then keep it safe for admin display
	$html = apply_filters('the_content', $post->post_content);
	$html = wp_kses_post($html);

	wp_send_json_success([
		'id'    => (int) $post->ID,
		'title' => (string) $post->post_title,
		'html'  => (string) $html,
	]);
});

/**
 * URL rules evaluator:
 * - If any EXCLUDE matches => FAIL
 * - If there are INCLUDE rules: ALL include rules must match
 */
function wiki_inline_url_rules_match(string $url, array $rules): bool {
	$includes = [];
	$excludes = [];

	foreach ($rules as $r) {
		if (!isset($r['mode'], $r['pattern'])) continue;
		if ($r['mode'] === 'exclude') $excludes[] = $r['pattern'];
		else $includes[] = $r['pattern'];
	}

	// any exclude match => fail
	foreach ($excludes as $pat) {
		if (@preg_match($pat, $url)) return false;
		if (@preg_match('~' . $pat . '~', $url)) return false;
	}

	// all includes must match
	foreach ($includes as $pat) {
		$ok = false;
		if (@preg_match($pat, $url)) $ok = true;
		if (!$ok && @preg_match('~' . $pat . '~', $url)) $ok = true;

		if (!$ok) return false;
	}

	return true;
}
