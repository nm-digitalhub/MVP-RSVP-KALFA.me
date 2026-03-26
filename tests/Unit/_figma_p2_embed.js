
const kB64 = "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";

function decodeB64(b) {
  const bin = atob(b);
  const len = bin.length;
  const bytes = new Uint8Array(len);
  for (let i = 0; i < len; i++) bytes[i] = bin.charCodeAt(i);
  return bytes;
}

const imgK = await figma.createImageAsync(decodeB64(kB64));

const brand = { r: 99 / 255, g: 91 / 255, b: 1 };
const slate900 = { r: 15 / 255, g: 23 / 255, b: 42 / 255 };
const slate50 = { r: 248 / 255, g: 250 / 255, b: 252 / 255 };
const white = { r: 1, g: 1, b: 1 };

function solid(c) {
  return [{ type: "SOLID", color: c }];
}

await figma.loadFontAsync({ family: "Inter", style: "Regular" });
await figma.loadFontAsync({ family: "Inter", style: "Medium" });
await figma.loadFontAsync({ family: "Inter", style: "Semi Bold" });

const shell = figma.getNodeById("8:2");
const shellX = shell && "x" in shell ? shell.x : 0;
const shellY = shell && "y" in shell ? shell.y : 0;

const oldMobile = figma.currentPage.children.find((c) => c.name === "Mobile — 390 (Logo_Icon K)");
if (oldMobile) oldMobile.remove();

const mobile = figma.createFrame();
mobile.name = "Mobile — 390 (Logo_Icon K)";
mobile.layoutMode = "VERTICAL";
mobile.primaryAxisSizingMode = "FIXED";
mobile.counterAxisSizingMode = "FIXED";
mobile.resize(390, 780);
mobile.x = shellX + 1480;
mobile.y = shellY;
mobile.fills = solid(white);
mobile.itemSpacing = 0;
figma.currentPage.appendChild(mobile);

const mTop = figma.createFrame();
mTop.name = "Mobile header";
mTop.layoutMode = "HORIZONTAL";
mTop.resize(390, 56);
mTop.fills = solid(slate900);
mTop.paddingLeft = 16;
mTop.paddingRight = 16;
mTop.itemSpacing = 12;
mTop.primaryAxisAlignItems = "CENTER";
mTop.layoutAlign = "STRETCH";

const mIconWrap = figma.createFrame();
mIconWrap.layoutMode = "HORIZONTAL";
mIconWrap.resize(36, 36);
mIconWrap.fills = [];
const mIcon = figma.createRectangle();
mIcon.resize(36, 36);
mIcon.fills = [{ type: "IMAGE", imageHash: imgK.hash, scaleMode: "FIT" }];
mIconWrap.appendChild(mIcon);
mTop.appendChild(mIconWrap);

const mGrow = figma.createFrame();
mGrow.layoutMode = "HORIZONTAL";
mGrow.layoutGrow = 1;
mGrow.fills = [];
mTop.appendChild(mGrow);

const mMenu = figma.createText();
mMenu.characters = "Menu";
mMenu.fontName = { family: "Inter", style: "Medium" };
mMenu.fontSize = 14;
mMenu.fills = solid(white);
mTop.appendChild(mMenu);

mobile.appendChild(mTop);

const mBody = figma.createFrame();
mBody.name = "Mobile body";
mBody.layoutMode = "VERTICAL";
mBody.layoutGrow = 1;
mBody.layoutAlign = "STRETCH";
mBody.primaryAxisSizingMode = "FIXED";
mBody.counterAxisSizingMode = "FIXED";
mBody.resize(390, 724);
mBody.fills = solid(slate50);
mBody.paddingLeft = 16;
mBody.paddingRight = 16;
mBody.paddingTop = 16;
mBody.paddingBottom = 16;
mBody.itemSpacing = 12;

const mCrumb = figma.createText();
mCrumb.characters = "Home / Acme Corp";
mCrumb.fontName = { family: "Inter", style: "Regular" };
mCrumb.fontSize = 11;
mCrumb.fills = [{ type: "SOLID", color: { r: 71 / 255, g: 85 / 255, b: 105 / 255 } }];

const mTitle = figma.createText();
mTitle.characters = "Overview";
mTitle.fontName = { family: "Inter", style: "Semi Bold" };
mTitle.fontSize = 22;
mTitle.fills = solid(slate900);

const mBtn = figma.createFrame();
mBtn.name = "Primary";
mBtn.layoutMode = "HORIZONTAL";
mBtn.paddingTop = 10;
mBtn.paddingBottom = 10;
mBtn.paddingLeft = 16;
mBtn.paddingRight = 16;
mBtn.cornerRadius = 10;
mBtn.fills = solid(brand);
const mBtnT = figma.createText();
mBtnT.characters = "New event";
mBtnT.fontName = { family: "Inter", style: "Medium" };
mBtnT.fontSize = 14;
mBtnT.fills = solid(white);
mBtn.appendChild(mBtnT);

const mNote = figma.createText();
mNote.characters = "Logo_Icon_(K) from your Figma file — E5ACGM4NjACwiUK3bfrF0m";
mNote.fontName = { family: "Inter", style: "Regular" };
mNote.fontSize = 10;
mNote.fills = [{ type: "SOLID", color: { r: 100 / 255, g: 116 / 255, b: 139 / 255 } }];

mBody.appendChild(mCrumb);
mBody.appendChild(mTitle);
mBody.appendChild(mBtn);
mBody.appendChild(mNote);
mobile.appendChild(mBody);

figma.viewport.scrollAndZoomIntoView([mobile]);

return { step: 2, mobileId: mobile.id };
