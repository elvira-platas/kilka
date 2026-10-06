/* Local DOM/browser checks; no WordPress data or network requests. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../plugins/kilka-reader/assets');
const css = ['controls.css', 'pagination.css', 'colors.css', 'intro.css'].map(f => fs.readFileSync(path.join(root, f), 'utf8')).join('\n');
const prose = '<p>' + 'Текст для проверки перехода к главе. '.repeat(60) + '</p>';
function fixture(headings, cover) {
  return `<!doctype html><html><head><meta charset="utf-8"><style>${css}
    .kilka-reader {max-width:700px;margin:auto} .kilka-reader-body {font:20px/1.7 serif}
    .kilka-reader-body h2,.kilka-reader-body h3 {margin:24px 0}
  </style></head><body class="kilka-reader-active"><main id="content" class="kilka-reading" tabindex="-1"><article>
  <div class="kilka-reader" data-kilka-reader><div class="kilka-reader-dock"><button class="kilka-reader-settings-toggle" aria-expanded="false" hidden>Settings</button></div>
  <div id="kilka-reader-settings" class="kilka-reader-toolbar" aria-label="Reading settings" hidden>
    <div class="kilka-reader-size" hidden><button data-reader-size="decrease">−</button><button data-reader-size="reset">100%</button><button data-reader-size="increase">+</button><span class="kilka-reader-status" data-label="Size"></span></div>
    <div class="kilka-reader-mode"><button data-reader-mode="scroll">Scroll</button><button data-reader-mode="pages">Pages</button></div>
    <button class="kilka-reader-fullscreen" data-enter-label="Fullscreen" data-exit-label="Exit" hidden><svg width="24" height="24"><path></path></svg></button><p class="kilka-reader-fullscreen-status"></p><nav class="kilka-reader-contents" aria-label="Contents" hidden><h2>Contents</h2><ol></ol></nav>
  </div><nav class="kilka-reader-pages" hidden><button data-reader-turn="previous">Previous</button><span class="kilka-reader-page-number" data-label="Page %1$s of %2$s"></span><button data-reader-turn="next">Next</button></nav>
  ${cover ? '<section class="kilka-reader-intro">Cover</section>' : ''}<div class="kilka-reader-body">${headings ? '<h2 id="author-anchor">Одинаковая глава</h2>'+prose+'<h3>Часть <em>вторая</em></h3>'+prose+'<h2>Одинаковая глава</h2>'+prose+'<h2> &nbsp; </h2><h4>Не глава</h4><h2>&lt;script&gt; &amp; Текст</h2>'+prose : prose}</div></div></article></main></body></html>`;
}
(async () => {
  const browser = await chromium.launch({headless:true, executablePath:process.env.CHROME_PATH || undefined, args:['--no-sandbox']});
  try {
    for (const width of [390,1360]) {
      const page = await browser.newPage({viewport:{width,height:900}});
      page.setDefaultTimeout(10000);
      const errors=[];
      page.on('pageerror', e=>errors.push(e.message));
      await page.route('**/*', route=>route.abort());
      async function load(headings, paginated=true, cover=false) {
        await page.goto('about:blank');
        await page.setContent(fixture(headings, cover));
        if (paginated) await page.addScriptTag({path:path.join(root,'pagination.js')});
        else await page.evaluate(()=>{ delete window.kilkaReaderPagination; });
        await page.addScriptTag({path:path.join(root,'reader.js')});
      }
      async function section(index) {
        const tab = page.locator('.kilka-reader-tabs button').nth(index);
        if (await tab.count()) await tab.click();
      }
      await load(true);
      await page.locator('.kilka-reader-settings-toggle').click();
      assert.equal(await page.locator('.kilka-reader-contents').isVisible(),true,'available in pages');
      await section(1);
      await page.locator('[data-reader-mode="scroll"]').click();
      await section(0);
      assert.equal(await page.locator('.kilka-reader-contents').isVisible(),true);
      assert.deepEqual(await page.locator('.kilka-reader-contents button').allTextContents(),['Одинаковая глава','Часть вторая','Одинаковая глава','<script> & Текст']);
      assert.equal(await page.locator('.kilka-reader-contents script').count(),0);
      await section(0);
      await page.locator('.kilka-reader-contents button').nth(2).click();
      assert.equal(await page.locator('#kilka-reader-settings').isVisible(),false);
      const position=await page.evaluate(()=>{
        const target=document.querySelectorAll('.kilka-reader-body h2')[1];
        return {focused:document.activeElement===target, offset:target.getBoundingClientRect().top-document.querySelector('main').getBoundingClientRect().top};
      });
      assert.equal(position.focused,true,'focus follows duplicate chapter');
      assert.ok(Math.abs(position.offset-24)<2,'target below reading viewport edge');
      assert.equal(await page.locator('#author-anchor').count(),1,'author ID unchanged');
      await page.keyboard.press('Tab');
      assert.equal(await page.locator('.kilka-reader-body h2').nth(1).getAttribute('tabindex'),null,'temporary tabindex cleaned');
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('[data-reader-size="increase"]').click();
      await section(0);
      await page.locator('.kilka-reader-contents button').nth(1).focus();
      await page.keyboard.press('Enter');
      assert.equal(await page.evaluate(()=>document.activeElement.tagName),'H3','keyboard navigation');
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('[data-reader-mode="pages"]').click();
      await section(0);
      assert.equal(await page.locator('.kilka-reader-contents').isVisible(),true);
      await section(1);
      await page.locator('[data-reader-mode="scroll"]').click();
      await page.screenshot({path:`/tmp/kilka-reader-contents-${width}.png`});
      // Jump directly from the cover, then reflow and continue normal page turns.
      await load(true,true,true);
      assert.equal(await page.locator('main').getAttribute('data-reader-intro-active'),'');
      async function checkChapter(index) {
        await page.waitForFunction(index => {
          const h = Array.from(document.querySelectorAll('.kilka-reader-body h2,.kilka-reader-body h3')).filter(h=>h.textContent.trim())[index];
          const v = document.querySelector('.kilka-reader-viewport');
          const r = document.createRange();
          const w = document.createTreeWalker(h,NodeFilter.SHOW_TEXT);
          let n; while ((n=w.nextNode()) && !n.textContent.trim()) {}
          r.setStart(n,n.textContent.search(/\S/)); r.setEnd(n,r.startOffset+1);
          const b=r.getBoundingClientRect(), box=v.getBoundingClientRect();
          return b.left >= box.left-1 && b.right <= box.right+1 && b.top >= box.top-1 && b.bottom <= box.bottom+1 && !v.inert;
        },index);
      }
      for (const index of [2,0,3,1]) {
        await page.locator('.kilka-reader-settings-toggle').click();
      await section(0);
        await page.locator('.kilka-reader-contents button').nth(index).focus();
        await page.keyboard.press('Enter');
        await checkChapter(index);
        assert.equal(await page.locator('#kilka-reader-settings').isVisible(),false);
        assert.equal(await page.locator('main').getAttribute('data-reader-intro-active'),null);
        assert.equal(await page.locator('.kilka-reader-viewport').getAttribute('aria-hidden'),'false');
      }
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('[data-reader-size="increase"]').click();
      await checkChapter(1);
      await page.keyboard.press('Escape');
      await page.setViewportSize({width:width===390?460:1000,height:760});
      await checkChapter(1);
      const before=Number((await page.locator('.kilka-reader-page-number').textContent()).split('/')[0]);
      await page.locator('[data-reader-turn="next"]').click();
      await page.waitForFunction(n=>Number(document.querySelector('.kilka-reader-page-number').textContent.split('/')[0])===n+1,before);
      await page.locator('[data-reader-turn="previous"]').click();
      await page.waitForFunction(n=>Number(document.querySelector('.kilka-reader-page-number').textContent.split('/')[0])===n,before);
      // A chapter jump must cancel a running slide, not leave a stale layer.
      await page.evaluate(()=>{
        document.querySelector('[data-reader-turn="next"]').click();
        document.querySelector('.kilka-reader-settings-toggle').click();
        document.querySelector('.kilka-reader-contents button').click();
      });
      await checkChapter(0);
      assert.equal(await page.locator('.kilka-reader-turn-stage').count(),0);
      assert.equal(await page.locator('.kilka-reader-body').evaluate(el=>el.style.visibility),'');
      await page.setViewportSize({width,height:900});
      // Real Fullscreen API, both reading modes, using the existing controls.
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('.kilka-reader-fullscreen').click();
      await page.waitForFunction(()=>!!document.fullscreenElement);
      await page.keyboard.press('Tab');
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(0);
      await page.locator('.kilka-reader-contents button').nth(2).click();
      await checkChapter(2);
      await page.waitForFunction(()=>document.querySelectorAll('.kilka-reader-contents [aria-current]').length===1);
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('[data-reader-mode="scroll"]').click();
      await section(0);
      await page.locator('.kilka-reader-contents button').nth(1).click();
      await page.waitForFunction(()=>document.querySelectorAll('.kilka-reader-contents button')[1].getAttribute('aria-current')==='location');
      await page.evaluate(()=>document.exitFullscreen());
      await page.waitForFunction(()=>!document.fullscreenElement);
      await load(false);
      await page.locator('.kilka-reader-settings-toggle').click();
      await section(1);
      await page.locator('[data-reader-mode="scroll"]').click();
      assert.equal(await page.locator('.kilka-reader-contents').isVisible(),false,'no chapters => hidden');
      await load(true,false);
      await page.locator('.kilka-reader-settings-toggle').click();
      assert.equal(await page.locator('.kilka-reader-contents').isVisible(),true,'scroll fallback');
      await section(0);
      await page.locator('.kilka-reader-contents button').nth(1).click();
      assert.equal(await page.evaluate(()=>document.activeElement.tagName),'H3');
      assert.deepEqual(errors,[]);
      await page.close();
      console.log(`PASS contents ${width}: chapter order, duplicates, text safety, focus, scroll, resize font, paged jumps from cover, reflow, next/previous, empty and fallback`);
    }
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
