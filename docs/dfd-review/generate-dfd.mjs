import { mkdirSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

// One graph supplies both the vector drawing and the editable diagrams.net file.
const dir = fileURLToPath(new URL('.', import.meta.url));
mkdirSync(dir, { recursive: true });
const W = 3000, H = 2200;
const nodes = [], flows = [];
const xml = s => String(s).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const node = (id, type, label, x, y, w, h, ref = '') => {
  const n = { id, type, label, x, y, w, h, ref }; nodes.push(n); return n;
};
const edge = (from, to, label, points, lx, ly) => flows.push({ from: from.id, to: to.id, label, points, lx, ly });
const origin = n => ({ x: 35 + ((n - 1) % 2) * 1490, y: 140 + Math.floor((n - 1) / 2) * 480 });
const process = (n, title) => {
  const {x,y} = origin(n);
  return node('p'+n, 'process', title, x+600, y+140, 270, 160, n+'.0');
};
const entity = (n, key, label, offset = 125, h = 190) => {
  const {x,y} = origin(n);
  return node('e'+n+key, 'entity', label, x+5, y+offset, 250, h);
};
const store = (n, key, label, offset = 165) => {
  const {x,y} = origin(n);
  return node('s'+n+key, 'store', label, x+1210, y+offset, 240, 90, key);
};
const leftIn = (e,p,label,yy) => edge(e,p,label,[[e.x+e.w,yy],[p.x,yy]], (e.x+e.w+p.x)/2, yy-24);
const leftOut = (p,e,label,yy) => edge(p,e,label,[[p.x,yy],[e.x+e.w,yy]], (e.x+e.w+p.x)/2, yy-24);
const read = (s,p,label,yy,py=yy) => {
  const pts = yy===py ? [[s.x,yy],[p.x+p.w,py]] : [[s.x,yy],[p.x+p.w+80,yy],[p.x+p.w+80,py],[p.x+p.w,py]];
  edge(s,p,label,pts,(s.x+p.x+p.w)/2,yy-24);
};
const write = (p,s,label,yy,py=yy) => {
  const pts = yy===py ? [[p.x+p.w,py],[s.x,yy]] : [[p.x+p.w,py],[p.x+p.w+45,py],[p.x+p.w+45,yy],[s.x,yy]];
  edge(p,s,label,pts,(s.x+p.x+p.w)/2,yy+24);
};

{
  const p=process(1,'Register Account'), e=entity(1,'r','Requestor'), d=store(1,'D1','Accounts');
  leftIn(e,p,'Personal and\nregistration details',p.y+42);
  leftOut(p,e,'Registration result',p.y+122);
  read(d,p,'Existing account data',d.y+20);
  write(p,d,'New account record',d.y+70);
}
{
  const p=process(2,'Authenticate Account'), e=entity(2,'u','System Users\nRequestor / Signatory /\nSMO In-Charge'), d=store(2,'D1','Accounts');
  leftIn(e,p,'Login credentials',p.y+42);
  leftOut(p,e,'Authentication result',p.y+122);
  write(p,d,'Account lookup criteria',d.y+20);
  flows.at(-1).ly=d.y-4;
  read(d,p,'Credential, role and\naccount-status data',d.y+70);
  flows.at(-1).ly=d.y+94;
}
{
  const p=process(3,'Submit Supply\nRequest'), e=entity(3,'r','Requestor /\nAuthorized Admin'), d=store(3,'D2','Supply Requests');
  leftIn(e,p,'Supply request details',p.y+42);
  leftOut(p,e,'Submission confirmation',p.y+122);
  write(p,d,'New supply request record',d.y+45);
}
{
  const p=process(4,'Approve Request'), e=entity(4,'a','Authorized Signatory /\nSchool Admin'), d=store(4,'D2','Supply Requests');
  leftIn(e,p,'Approval decision\nand remarks',p.y+42);
  leftOut(p,e,'Pending requests and\nreview details',p.y+122);
  read(d,p,'Request details and\napproval history',d.y+20);
  write(p,d,'Approval decision and\ncurrent approval stage',d.y+70);
}
{
  const p=process(5,'Validate Request'), e=entity(5,'s','SMO In-Charge',110,150), r=entity(5,'r','Requestor',335,65), d=store(5,'D2','Supply Requests');
  leftIn(e,p,'Validation decision\nand remarks',p.y+30);
  leftOut(p,e,'Fully approved requests\nfor validation',p.y+100);
  edge(p,r,'Validation result',[[p.x+75,p.y+p.h],[p.x+75,r.y+32],[r.x+r.w,r.y+32]],(p.x+75+r.x+r.w)/2,r.y+8);
  read(d,p,'Fully approved\nrequest data',d.y+20);
  write(p,d,'Validation status\nand remarks',d.y+70);
}
{
  const p=process(6,'Manage Supply'), e=entity(6,'s','SMO In-Charge',110,150), r=entity(6,'r','Requestor',335,65), d=store(6,'D2','Supply Requests',65), stock=store(6,'D3','Inventory',310);
  leftIn(e,p,'Fulfillment details and\nstock adjustments',p.y+30);
  leftOut(p,e,'Processing confirmation',p.y+100);
  edge(p,r,'Fulfillment / release update',[[p.x+75,p.y+p.h],[p.x+75,r.y+32],[r.x+r.w,r.y+32]],(p.x+75+r.x+r.w)/2,r.y+8);
  read(d,p,'Validated requests\nfor fulfillment',d.y+20,p.y+25);
  flows.at(-1).points=[[d.x,d.y+20],[p.x+p.w,p.y+25]];
  flows.at(-1).ly=(d.y+20+p.y+25)/2-34;
  write(p,d,'Processing / fulfillment\nstatus and release record',d.y+70,p.y+65);
  flows.at(-1).points=[[p.x+p.w,p.y+65],[d.x,d.y+70]];
  flows.at(-1).ly=(d.y+70+p.y+65)/2+34;
  read(stock,p,'Current inventory data',stock.y+20,p.y+105);
  flows.at(-1).points=[[stock.x,stock.y+20],[p.x+p.w,p.y+105]];
  flows.at(-1).ly=(stock.y+20+p.y+105)/2-34;
  write(p,stock,'Stock changes and\nissuance data',stock.y+70,p.y+145);
  flows.at(-1).points=[[p.x+p.w,p.y+145],[stock.x,stock.y+70]];
  flows.at(-1).ly=(stock.y+70+p.y+145)/2+34;
}
{
  const p=process(7,'Track Request\nStatus'), e=entity(7,'r','Requestor'), d=store(7,'D2','Supply Requests');
  leftIn(e,p,'Request ID and\nstatus lookup criteria',p.y+42);
  leftOut(p,e,'Current request status\nand progress details',p.y+122);
  write(p,d,'Request lookup criteria',d.y+20);
  flows.at(-1).ly=d.y-4;
  read(d,p,'Request status and\nprogress records',d.y+70);
  flows.at(-1).ly=d.y+94;
}
{
  const p=process(8,'Send Notification'), e=entity(8,'u','Notification Recipients\nSMO In-Charge /\nRequestor /\nAssigned Signatory',125,190), a=store(8,'D1','Accounts',65), d=store(8,'D2','Supply Requests',310);
  leftOut(p,e,'Approval / status notification;\ndeadline warning or overdue alert',p.y+80);
  read(a,p,'Recipient account and\ncontact data',a.y+45,p.y+40);
  read(d,p,'Approval / request status,\nsubmission and due-date data',d.y+45,p.y+120);
}

const lines = (s, x, y, size=21, weight=400, fill='#151515') => {
  const parts=s.split('\n'), lineHeight=size*1.3;
  return `<text x="${x}" y="${y-(parts.length-1)*lineHeight/2}" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="${size}" font-weight="${weight}" fill="${fill}">${parts.map((t,i)=>`<tspan x="${x}" dy="${i ? lineHeight : 0}">${xml(t)}</tspan>`).join('')}</text>`;
};
const svg = [`<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}"><defs><marker id="arrow" markerWidth="10" markerHeight="8" refX="9" refY="4" orient="auto" markerUnits="userSpaceOnUse"><path d="M0,0 L10,4 L0,8 Z" fill="#151515"/></marker></defs><rect width="100%" height="100%" fill="white"/>`];
svg.push(lines('Figure 3. Level 1 Data Flow Diagram',W/2,49,36,700));
svg.push(lines('Supply Management Office Request Tracking and Inventory System',W/2,94,25));
// Flows are drawn before nodes, with labels on white backgrounds.
for (const f of flows) {
  svg.push(`<polyline points="${f.points.map(p=>p.join(',')).join(' ')}" fill="none" stroke="#151515" stroke-width="2.3" marker-end="url(#arrow)"/>`);
  const parts=f.label.split('\n'), width=Math.max(...parts.map(s=>s.length))*10.3+18, height=parts.length*25+5;
  svg.push(`<rect x="${f.lx-width/2}" y="${f.ly-height/2}" width="${width}" height="${height}" fill="white"/>`);
  svg.push(lines(f.label,f.lx,f.ly,20));
}
for(const n of nodes) {
  if(n.type==='process') {
    svg.push(`<rect x="${n.x}" y="${n.y}" width="${n.w}" height="${n.h}" rx="18" fill="white" stroke="#151515" stroke-width="2.5"/><path d="M${n.x},${n.y+43} H${n.x+n.w}" stroke="#151515" stroke-width="2"/>`);
    svg.push(lines(n.ref,n.x+n.w/2,n.y+23,23,700),lines(n.label,n.x+n.w/2,n.y+103,23,700));
  } else if(n.type==='entity') {
    svg.push(`<rect x="${n.x}" y="${n.y}" width="${n.w}" height="${n.h}" fill="white" stroke="#151515" stroke-width="2.5"/>`,lines(n.label,n.x+n.w/2,n.y+n.h/2,20,700));
  } else {
    svg.push(`<path d="M${n.x+n.w},${n.y} H${n.x} V${n.y+n.h} H${n.x+n.w} M${n.x+55},${n.y} V${n.y+n.h}" fill="white" stroke="#151515" stroke-width="2.5"/>`);
    svg.push(lines(n.ref,n.x+27,n.y+n.h/2,22,700),lines(n.label,n.x+55+(n.w-55)/2,n.y+n.h/2,20,700));
  }
}
svg.push(`<path d="M50,2070 H2950" stroke="#777" stroke-width="1"/>`);
svg.push(lines('Repeated entities and stores represent the SAME roles and logical data stores; they are repeated for readability.',W/2,2107,22));
svg.push(lines('Grouped role labels share the shown inputs / outputs. Notification recipients depend on the event and permissions.',W/2,2143,22));
svg.push(lines('Process numbers identify functions, not execution order. Deadline policy: three working days; warning and overdue checks belong to process 8.0.',W/2,2178,21));
svg.push('</svg>');
writeFileSync(dir+'figure-3-level-1-dfd.svg',svg.join('\n'));

const cells=['<mxCell id="0"/><mxCell id="1" parent="0"/>'];
const cellText=(id,label,x,y,w,h,size=22)=>cells.push(`<mxCell id="${id}" value="${xml(label)}" style="text;html=0;align=center;verticalAlign=middle;whiteSpace=wrap;fillColor=#ffffff;fontFamily=Arial;fontSize=${size};" vertex="1" parent="1"><mxGeometry x="${x}" y="${y}" width="${w}" height="${h}" as="geometry"/></mxCell>`);
cellText('title','Figure 3. Level 1 Data Flow Diagram',200,15,2600,55,36);
cellText('subtitle','Supply Management Office Request Tracking and Inventory System',200,72,2600,40,25);
for (const n of nodes) {
  const style=n.type==='process'?'rounded=1;arcSize=12;':n.type==='store'?'shape=partialRectangle;right=0;':'rounded=0;';
  cells.push(`<mxCell id="${n.id}" value="${xml(n.type==='process'?n.ref+'\n\n'+n.label:n.type==='store'?'':n.label)}" style="${style}whiteSpace=wrap;html=0;fillColor=#ffffff;strokeColor=#151515;strokeWidth=2;fontFamily=Arial;fontSize=21;fontStyle=1;" vertex="1" parent="1"><mxGeometry x="${n.x}" y="${n.y}" width="${n.w}" height="${n.h}" as="geometry"/></mxCell>`);
  if(n.type==='store') {
    cells.push(`<mxCell id="${n.id}-separator" value="" style="shape=line;strokeColor=#151515;strokeWidth=2;" vertex="1" parent="1"><mxGeometry x="${n.x+55}" y="${n.y}" width="0" height="${n.h}" as="geometry"/></mxCell>`);
    cellText(n.id+'-ref',n.ref,n.x,n.y,55,n.h,22);
    cellText(n.id+'-name',n.label,n.x+55,n.y,n.w-55,n.h,20);
  }
}
flows.forEach((f,i)=>{
  const source=nodes.find(n=>n.id===f.from),target=nodes.find(n=>n.id===f.to),start=f.points[0],end=f.points.at(-1);
  const exitX=(start[0]-source.x)/source.w,exitY=(start[1]-source.y)/source.h,entryX=(end[0]-target.x)/target.w,entryY=(end[1]-target.y)/target.h;
  cells.push(`<mxCell id="f${i}" value="" style="edgeStyle=none;html=0;endArrow=block;endFill=1;strokeColor=#151515;strokeWidth=2;exitX=${exitX};exitY=${exitY};entryX=${entryX};entryY=${entryY};exitPerimeter=0;entryPerimeter=0;" edge="1" parent="1" source="${f.from}" target="${f.to}"><mxGeometry relative="1" as="geometry"><Array as="points">${f.points.slice(1,-1).map(p=>`<mxPoint x="${p[0]}" y="${p[1]}"/>`).join('')}</Array></mxGeometry></mxCell>`);
  cellText('flabel'+i,f.label,f.lx-171,f.ly-30,342,60,20);
});
cellText('note1','Repeated entities and stores represent the same roles and logical data stores; they are repeated for readability.',50,2080,2900,40,22);
cellText('note2','Grouped role labels share the shown inputs / outputs. Notification recipients depend on the event and permissions.',50,2120,2900,35,22);
cellText('note3','Process numbers identify functions, not execution order. Deadline policy: three working days; warning and overdue checks belong to process 8.0.',50,2155,2900,35,21);
writeFileSync(dir+'figure-3-level-1-dfd.drawio',`<?xml version="1.0" encoding="UTF-8"?><mxfile host="app.diagrams.net"><diagram id="figure3" name="Figure 3 - Level 1 DFD"><mxGraphModel dx="3000" dy="2200" grid="1" gridSize="10" page="1" pageScale="1" pageWidth="3000" pageHeight="2200"><root>${cells.join('\n')}</root></mxGraphModel></diagram></mxfile>`);
writeFileSync(dir+'diagram-data.json',JSON.stringify({width:W,height:H,nodes,flows},null,2));
console.log(`Created vector and editable diagram: ${nodes.length} symbols, ${flows.length} data flows.`);
