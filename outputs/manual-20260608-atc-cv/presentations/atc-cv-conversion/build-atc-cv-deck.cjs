const pptxgen = require("pptxgenjs");
const path = require("path");
const fs = require("fs");

const root = "C:\\laragon\\www\\cv-studio\\outputs\\manual-20260608-atc-cv\\presentations\\atc-cv-conversion";
const assets = path.join(root, "assets");
const crops = path.join(assets, "crops");
const outputDir = path.join(root, "output");
const outputFile = path.join(outputDir, "ATC-conversion-a-CVs.pptx");

fs.mkdirSync(outputDir, { recursive: true });

const pptx = new pptxgen();
pptx.layout = "LAYOUT_WIDE";
pptx.author = "OpenAI Codex";
pptx.company = "CV Studio";
pptx.subject = "Presentacion ejecutiva ATC";
pptx.title = "ATC - Conversion a CVs";
pptx.lang = "es-MX";
pptx.theme = {
  headFontFace: "Aptos Display",
  bodyFontFace: "Aptos",
  lang: "es-MX",
};

const C = {
  ink: "172033",
  navy: "15233B",
  blue: "3157D5",
  mint: "16A085",
  gold: "D59124",
  soft: "F4F7FB",
  line: "D9E3F0",
  white: "FFFFFF",
  slate: "5F6F89",
};

function addPageNumber(slide, n) {
  slide.addText(String(n).padStart(2, "0"), {
    x: 12.55,
    y: 7.0,
    w: 0.45,
    h: 0.2,
    fontFace: "Aptos",
    fontSize: 9,
    color: C.slate,
    align: "right",
    margin: 0,
  });
}

function addKicker(slide, text, x, y, w = 2.3) {
  slide.addText(text, {
    x,
    y,
    w,
    h: 0.22,
    fontFace: "Aptos",
    fontSize: 10,
    bold: true,
    color: C.blue,
    charSpace: 1.1,
    margin: 0,
  });
}

function addTitle(slide, text, x, y, w, h = 0.8, size = 24, color = C.ink) {
  slide.addText(text, {
    x,
    y,
    w,
    h,
    fontFace: "Aptos Display",
    fontSize: size,
    bold: true,
    color,
    margin: 0,
    breakLine: false,
    valign: "mid",
  });
}

function addBody(slide, text, x, y, w, h, opts = {}) {
  slide.addText(text, {
    x,
    y,
    w,
    h,
    fontFace: "Aptos",
    fontSize: opts.size || 12,
    color: opts.color || C.slate,
    bold: opts.bold || false,
    margin: opts.margin !== undefined ? opts.margin : 0,
    breakLine: false,
    valign: opts.valign || "top",
    fit: "shrink",
  });
}

function addRoundCard(slide, x, y, w, h, fill = C.white, line = C.line) {
  slide.addShape(pptx.ShapeType.roundRect, {
    x,
    y,
    w,
    h,
    rectRadius: 0.08,
    fill: { color: fill },
    line: { color: line, pt: 1 },
  });
}

function addPill(slide, text, x, y, w) {
  slide.addShape(pptx.ShapeType.roundRect, {
    x,
    y,
    w,
    h: 0.42,
    rectRadius: 0.08,
    fill: { color: "22304A" },
    line: { color: "22304A", pt: 1 },
  });
  slide.addText(text, {
    x: x + 0.12,
    y: y + 0.06,
    w: w - 0.24,
    h: 0.24,
    fontFace: "Aptos",
    fontSize: 10,
    bold: true,
    color: C.white,
    margin: 0,
    align: "center",
  });
}

function addSectionCard(slide, cfg) {
  addRoundCard(slide, cfg.x, cfg.y, cfg.w, cfg.h, C.white, C.line);
  slide.addShape(pptx.ShapeType.roundRect, {
    x: cfg.x + 0.18,
    y: cfg.y + 0.2,
    w: 0.46,
    h: 0.32,
    rectRadius: 0.06,
    fill: { color: cfg.badgeFill || C.soft },
    line: { color: cfg.badgeFill || C.soft, pt: 1 },
  });
  slide.addText(cfg.badge, {
    x: cfg.x + 0.18,
    y: cfg.y + 0.245,
    w: 0.46,
    h: 0.12,
    fontSize: 9,
    bold: true,
    color: cfg.badgeColor || C.blue,
    align: "center",
    margin: 0,
  });
  addBody(slide, cfg.title, cfg.x + 0.18, cfg.y + 0.62, cfg.w - 0.36, 0.35, {
    size: 17,
    bold: true,
    color: C.ink,
  });
  addBody(slide, cfg.body, cfg.x + 0.18, cfg.y + 1.0, cfg.w - 0.36, cfg.h - 1.18, {
    size: 11,
  });
}

function addBulletBlock(slide, cfg) {
  addRoundCard(slide, cfg.x, cfg.y, cfg.w, cfg.h, C.white, C.line);
  slide.addShape(pptx.ShapeType.roundRect, {
    x: cfg.x + 0.18,
    y: cfg.y + 0.2,
    w: 0.4,
    h: 0.34,
    rectRadius: 0.06,
    fill: { color: cfg.badgeFill || "EAF2FF" },
    line: { color: cfg.badgeFill || "EAF2FF", pt: 1 },
  });
  slide.addText(cfg.badge, {
    x: cfg.x + 0.18,
    y: cfg.y + 0.255,
    w: 0.4,
    h: 0.1,
    fontSize: 9,
    bold: true,
    color: cfg.badgeColor || C.blue,
    align: "center",
    margin: 0,
  });
  addBody(slide, cfg.title, cfg.x + 0.18, cfg.y + 0.7, cfg.w - 0.36, 0.3, {
    size: 16,
    bold: true,
    color: C.ink,
  });
  addBody(slide, cfg.body, cfg.x + 0.18, cfg.y + 1.05, cfg.w - 0.36, cfg.h - 1.25, {
    size: 11,
  });
}

function addImageFrame(slide, imagePath, x, y, w, h) {
  addRoundCard(slide, x, y, w, h, C.white, C.line);
  slide.addImage({
    path: imagePath,
    x: x + 0.1,
    y: y + 0.1,
    w: w - 0.2,
    h: h - 0.2,
  });
}

function addBulletList(slide, items, x, y, w, lineGap = 0.58) {
  items.forEach((item, idx) => {
    slide.addShape(pptx.ShapeType.ellipse, {
      x,
      y: y + idx * lineGap + 0.08,
      w: 0.1,
      h: 0.1,
      fill: { color: C.mint },
      line: { color: C.mint, pt: 1 },
    });
    addBody(slide, item, x + 0.18, y + idx * lineGap, w - 0.18, 0.38, {
      size: 13,
      color: C.ink,
    });
  });
}

{
  const slide = pptx.addSlide();
  slide.background = { color: C.soft };

  slide.addShape(pptx.ShapeType.rect, {
    x: 0,
    y: 0,
    w: 7.05,
    h: 7.5,
    fill: { color: C.navy },
    line: { color: C.navy, pt: 1 },
  });

  addKicker(slide, "ATC | PRESENTACION EJECUTIVA", 0.65, 0.58, 2.9);
  addTitle(slide, "Conversion de CVs lista\npara operar", 0.65, 1.15, 5.2, 1.35, 28, C.white);
  addBody(
    slide,
    "CV Studio recibe un CV, lo estructura y lo deja listo para revisar, ajustar y presentar con un formato consistente.",
    0.65,
    2.7,
    5.1,
    1.0,
    { size: 13, color: "D7E1EE" }
  );

  addPill(slide, "Menos captura manual", 0.65, 4.2, 1.9);
  addPill(slide, "Formato estandar", 2.7, 4.2, 1.7);
  addPill(slide, "Salida inmediata", 4.55, 4.2, 1.7);

  addBody(slide, "Objetivo para ATC", 0.65, 5.25, 1.9, 0.22, {
    size: 10,
    bold: true,
    color: "9DB2FF",
  });
  addBody(
    slide,
    "Pasar de CV recibido a CV utilizable en menos pasos, con mejor consistencia y con control antes de descargar.",
    0.65,
    5.55,
    5.2,
    0.9,
    { size: 12, color: C.white }
  );

  addImageFrame(slide, path.join(crops, "cv-show-crop.png"), 7.45, 0.75, 5.1, 5.95);
  addPageNumber(slide, 1);
}

{
  const slide = pptx.addSlide();
  slide.background = { color: C.soft };

  addKicker(slide, "FOCO DE LA PROPUESTA", 0.72, 0.45, 2.5);
  addTitle(slide, "Lo que ATC compra realmente", 0.72, 0.78, 5.5, 0.55, 24);

  addBulletBlock(slide, {
    x: 0.72,
    y: 1.55,
    w: 4.15,
    h: 1.25,
    badge: "01",
    title: "Entrada simple",
    body: "El equipo carga un PDF, DOCX o TXT sin rehacer el CV desde cero.",
  });

  addBulletBlock(slide, {
    x: 0.72,
    y: 3.0,
    w: 4.15,
    h: 1.45,
    badge: "02",
    title: "Lectura estructurada",
    body: "El sistema separa perfil, experiencia, educacion, software, habilidades, idiomas y certificaciones.",
  });

  addBulletBlock(slide, {
    x: 0.72,
    y: 4.65,
    w: 4.15,
    h: 1.25,
    badge: "03",
    title: "Salida util",
    body: "ATC revisa, ajusta y descarga un CV listo para presentar.",
  });

  addImageFrame(slide, path.join(crops, "preview-crop.png"), 5.25, 1.55, 7.25, 4.35);

  slide.addShape(pptx.ShapeType.roundRect, {
    x: 5.25,
    y: 6.1,
    w: 7.25,
    h: 0.68,
    rectRadius: 0.06,
    fill: { color: "E8F7F2" },
    line: { color: "B9E6D7", pt: 1 },
  });
  addBody(
    slide,
    "Valor clave: antes de guardar, el reclutador decide que informacion aplicar al CV final.",
    5.48,
    6.28,
    6.8,
    0.2,
    { size: 11, bold: true, color: "116B53" }
  );

  addPageNumber(slide, 2);
}

{
  const slide = pptx.addSlide();
  slide.background = { color: C.soft };

  addKicker(slide, "IMPACTO PARA NEGOCIO", 0.72, 0.45, 2.3);
  addTitle(slide, "Valor para operacion y para direccion", 0.72, 0.78, 6.2, 0.55, 24);

  addSectionCard(slide, {
    x: 0.72,
    y: 1.7,
    w: 2.85,
    h: 2.0,
    badge: "A",
    title: "Velocidad",
    body: "Reduce tiempo de captura y acelera la preparacion del CV antes de enviarlo al cliente o a la terna interna.",
  });

  addSectionCard(slide, {
    x: 3.85,
    y: 1.7,
    w: 2.85,
    h: 2.0,
    badge: "B",
    title: "Consistencia",
    body: "Estandariza la estructura del CV aunque la fuente original llegue en formatos y estilos distintos.",
  });

  addSectionCard(slide, {
    x: 6.98,
    y: 1.7,
    w: 2.85,
    h: 2.0,
    badge: "C",
    title: "Control",
    body: "No automatiza a ciegas: deja una previsualizacion para validar antes de aplicar cambios.",
  });

  addSectionCard(slide, {
    x: 10.11,
    y: 1.7,
    w: 2.17,
    h: 2.0,
    badge: "D",
    title: "Salida",
    body: "Entrega CV descargable en PDF y Word desde el mismo flujo.",
  });

  addRoundCard(slide, 0.72, 4.2, 11.56, 1.8, C.white, C.line);
  addBody(slide, "En una frase", 0.98, 4.5, 1.4, 0.2, {
    size: 10,
    bold: true,
    color: C.blue,
  });
  addTitle(
    slide,
    "ATC gana un conversor operativo de CVs,\nno otro modulo mas por administrar.",
    0.98,
    4.78,
    6.0,
    0.95,
    22,
    C.ink
  );
  addBulletList(
    slide,
    [
      "Carga un CV existente.",
      "Extrae la informacion importante.",
      "La deja lista para presentacion.",
    ],
    7.1,
    4.7,
    4.5,
    0.48
  );

  addPageNumber(slide, 3);
}

{
  const slide = pptx.addSlide();
  slide.background = { color: C.soft };

  addKicker(slide, "DEMO EN VIVO", 0.72, 0.45, 1.8);
  addTitle(slide, "Lo unico que vale la pena mostrar en vivo", 0.72, 0.78, 6.5, 0.55, 24);

  addImageFrame(slide, path.join(crops, "upload-crop.png"), 0.72, 1.65, 4.55, 1.8);

  addRoundCard(slide, 0.72, 3.72, 4.55, 2.1, C.white, C.line);
  addBody(slide, "Guion sugerido", 0.98, 4.0, 1.6, 0.2, {
    size: 10,
    bold: true,
    color: C.blue,
  });
  addBulletList(
    slide,
    [
      "Subir un CV real.",
      "Mostrar la previsualizacion.",
      "Aplicar al formato final.",
      "Descargar el CV.",
    ],
    0.98,
    4.28,
    3.95,
    0.42
  );

  addRoundCard(slide, 5.6, 1.65, 6.7, 4.17, C.white, C.line);
  addBody(slide, "Que debe escuchar direccion", 5.88, 2.0, 2.4, 0.2, {
    size: 10,
    bold: true,
    color: C.blue,
  });
  addBulletList(
    slide,
    [
      "No estamos vendiendo el proceso completo de reclutamiento; estamos resolviendo el cuello de botella de convertir CVs.",
      "La promesa es velocidad con control, no automatizacion opaca.",
      "La prueba de valor es ver un CV entrar, estructurarse y quedar listo para salida en minutos.",
      "El equipo de ATC mantiene la ultima palabra antes de descargar o compartir.",
    ],
    5.88,
    2.35,
    5.95,
    0.72
  );

  addPageNumber(slide, 4);
}

{
  const slide = pptx.addSlide();
  slide.background = { color: C.navy };

  addKicker(slide, "CIERRE", 0.72, 0.55, 1.0);
  addTitle(slide, "Siguiente paso recomendado", 0.72, 0.92, 5.8, 0.6, 26, C.white);
  addBody(
    slide,
    "Si ATC valida la demo, la forma mas clara de cerrar la venta es un piloto corto con CVs reales.",
    0.72,
    1.62,
    5.7,
    0.6,
    { size: 13, color: "D7E1EE" }
  );

  addSectionCard(slide, {
    x: 0.72,
    y: 2.5,
    w: 3.8,
    h: 2.2,
    badge: "01",
    title: "Piloto corto",
    body: "Validar la conversion con un lote pequeno de CVs reales de ATC.",
    badgeFill: "EAF2FF",
  });

  addSectionCard(slide, {
    x: 4.76,
    y: 2.5,
    w: 3.8,
    h: 2.2,
    badge: "02",
    title: "Ajuste de formato",
    body: "Confirmar que el CV final cumple el estilo y el nivel de presentacion esperado por ATC.",
    badgeFill: "EAF2FF",
  });

  addSectionCard(slide, {
    x: 8.8,
    y: 2.5,
    w: 3.5,
    h: 2.2,
    badge: "03",
    title: "Medicion simple",
    body: "Comparar tiempo manual vs. tiempo con conversion asistida.",
    badgeFill: "EAF2FF",
  });

  slide.addShape(pptx.ShapeType.roundRect, {
    x: 0.72,
    y: 5.35,
    w: 11.58,
    h: 1.05,
    rectRadius: 0.08,
    fill: { color: "1E304F" },
    line: { color: "39537D", pt: 1 },
  });
  addBody(
    slide,
    "Decision sugerida para direccion: aprobar un piloto enfocado unicamente en conversion a CVs.",
    1.02,
    5.72,
    10.9,
    0.2,
    { size: 14, bold: true, color: C.white }
  );

  addPageNumber(slide, 5);
}

pptx.writeFile({ fileName: outputFile });
console.log(outputFile);
