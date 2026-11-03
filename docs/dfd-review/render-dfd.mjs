import assert from 'node:assert/strict';
import { readFileSync, writeFileSync, existsSync, mkdtempSync } from 'node:fs';
import { spawn } from 'node:child_process';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { pathToFileURL, fileURLToPath } from 'node:url';

const dir=fileURLToPath(new URL('.',import.meta.url));
const profile=mkdtempSync(join(tmpdir(),'htc-dfd-preview-'));
const child=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
  '--headless','--disable-gpu','--disable-background-networking','--disable-extensions',
  '--no-first-run','--no-default-browser-check','--remote-debugging-port=0',
  '--allow-file-access-from-files','--user-data-dir='+profile,'about:blank'
],{windowsHide:true,stdio:'ignore'});
let ws;
try {
  let port;
  for(let i=0;i<100;i++) {
    const file=join(profile,'DevToolsActivePort');
    if(existsSync(file)) { port=readFileSync(file,'utf8').split('\n')[0]; break; }
    await new Promise(r=>setTimeout(r,100));
  }
  assert.ok(port,'Chrome started');
  const pages=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();
  ws=new WebSocket(pages.find(p=>p.type==='page').webSocketDebuggerUrl);
  await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
  let id=0; const pending=new Map();
  ws.onmessage=e=>{const m=JSON.parse(e.data);if(!pending.has(m.id))return;const h=pending.get(m.id);pending.delete(m.id);m.error?h.reject(new Error(JSON.stringify(m.error))):h.resolve(m.result);};
  const send=(method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});ws.send(JSON.stringify({id:key,method,params}));});
  const evaluate=async expression=>{
    const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});
    assert.ok(!r.exceptionDetails,JSON.stringify(r.exceptionDetails));return r.result.value;
  };
  await send('Emulation.setDeviceMetricsOverride',{width:1500,height:1100,deviceScaleFactor:2,mobile:false});
  await send('Page.enable');
  const svg=readFileSync(dir+'figure-3-level-1-dfd.svg','utf8');
  const html=`<!doctype html><html><head><meta charset="UTF-8"><style>@page{size:A3 landscape;margin:10mm}html,body{margin:0;background:white}svg{display:block;width:100vw;height:auto}@media print{svg{width:377mm;height:auto;margin:0 auto}}</style></head><body>${svg}</body></html>`;
  writeFileSync(dir+'figure-3-level-1-dfd.html',html);
  await send('Page.navigate',{url:pathToFileURL(dir+'figure-3-level-1-dfd.html').href});
  for(let i=0;i<50;i++) {
    if(await evaluate('document.readyState === "complete" && !!document.querySelector("svg")'))break;
    await new Promise(r=>setTimeout(r,100));
  }
  const checks=await evaluate(`(()=>{const svg=document.querySelector('svg');const labels=[...svg.querySelectorAll('text')];return {svg:!!svg,labelCount:labels.length,clipped:labels.filter(t=>{const b=t.getBBox();return b.x<0||b.y<0||b.x+b.width>3000||b.y+b.height>2200}).map(t=>t.textContent)}})()`);
  assert.deepEqual(checks.clipped,[],'All text fits inside the diagram');
  const png=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
  writeFileSync(dir+'figure-3-level-1-dfd.png',Buffer.from(png.data,'base64'));
  // A smaller inspection copy is convenient for viewing in the chat.
  await send('Emulation.setDeviceMetricsOverride',{width:1500,height:1100,deviceScaleFactor:1,mobile:false});
  const preview=await send('Page.captureScreenshot',{format:'png'});
  writeFileSync(dir+'figure-3-preview.png',Buffer.from(preview.data,'base64'));
  const pdf=await send('Page.printToPDF',{printBackground:true,preferCSSPageSize:true});
  writeFileSync(dir+'figure-3-level-1-dfd.pdf',Buffer.from(pdf.data,'base64'));
  const graph=JSON.parse(readFileSync(dir+'diagram-data.json','utf8'));
  const types=new Map(graph.nodes.map(n=>[n.id,n.type]));
  for(const f of graph.flows)assert.ok(types.get(f.from)==='process'||types.get(f.to)==='process','Each flow has a process endpoint');
  for(const p of graph.nodes.filter(n=>n.type==='process')) {
    assert.ok(graph.flows.some(f=>f.from===p.id),p.id+' has output');
    assert.ok(graph.flows.some(f=>f.to===p.id),p.id+' has input');
  }
  assert.equal(new Set(graph.nodes.filter(n=>n.type==='store').map(n=>n.ref)).size,3);
  console.log(JSON.stringify({checks,processes:8,logicalStores:3,dataFlows:graph.flows.length,outputs:['PNG (3000 x 2200)','A3 landscape PDF','SVG','editable diagrams.net file']}));
} finally {
  ws?.close();child.kill();
}
