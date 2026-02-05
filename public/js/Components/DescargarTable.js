import "/js/lib/xlsx.min.js";

export const DescargarTable = {
  exportToExcel(table,sheetname='Sheet1',filename='File.xlsx') {
    const workbook = XLSX.utils.book_new();
    const worksheet = this.htmlTableToWorksheet(table);
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetname);
    XLSX.writeFile(workbook,filename);
  },
  htmlTableToWorksheet(table) {
    const ws = XLSX.utils.table_to_sheet(table,{raw: true});
    const range = XLSX.utils.decode_range(ws['!ref']);
    {
      const max_columns = Array.from(table.querySelectorAll('tr')).map(function(tr){
        return tr.querySelectorAll('td,th').length;
      }).reduce((carry,val) => Math.max(carry,val));
      
      const tr = Array.from(table.querySelectorAll('tr')).find(function(tr){
        return tr.querySelectorAll('td,th').length == max_columns
      });
      
      ws['!cols'] = [];
      tr.querySelectorAll('td,th').forEach(function(tdh,thidx){
        const style = window.getComputedStyle(tdh);
        const width = tdh.offsetWidth;
        const excelWidth = width/7.5;
        ws['!cols'].push({
          wch: excelWidth,
          width: excelWidth // Some versions use 'width' instead of 'wch'
        });
      });
    }
    /*
     * Hago todo este barullo porque necesito la esquina superior izquierda de cada celda
     * para estilizarlo bien, y como las celdas pueden tener rowSpan y colSpan hago lo siguiente:
     * 1. Encuentro las dimensiones maximas de la tabla y genero una matriz
     * 2. Para cada celda, la marco su posicion y la extiendo si tiene colSpan>1 o rowSpan>1
     *    Notablemente tengo que reencontrar la posición para cada celda buscando desde arriba a la izquierda
     * */
    let max_width = -1/0;
    let max_height = 0;
    for(const child of table.children){
      if(!(child.tagName == 'TBODY' || child.tagName == 'THEAD')) continue;
      for(const tr of child.rows){
        let w = 0;
        for(const htmlCell of tr.cells){
          w+=htmlCell.colSpan;
        }
        max_width = Math.max(max_width,w);
        max_height++;
      }
    }
    
    const used = Array.from(
      {length: max_height},
      () => Array.from({length: max_width}, () => null)
    );
    
    for(const child of table.children){
      if(!(child.tagName == 'TBODY' || child.tagName == 'THEAD')) continue;
      for(const tr of child.rows){
        for(const htmlCell of tr.cells){
          let row = 0;
          let col = 0;
          let found_spot = false;
          for(;row<max_height;row++){
            for(col=0;col<max_width;col++){
              if(used[row][col] === null){
                found_spot = true;
                break;
              }
            }
            if(found_spot){
              break;
            }
          }
          
          if(!found_spot){
            throw 'Unreachable';
          }
          
          const rowSpan = htmlCell?.rowSpan || 1;
          const colSpan = htmlCell?.colSpan || 1;
          if(rowSpan > 1 || colSpan > 1){
            ws['!merges'] = ws['!merges'] ?? [];
            ws['!merges'].push({
              s: {
                r: row,
                c: col
              },
              e: {
                r: (row+rowSpan-1),
                c: (col+colSpan-1),
              }
            });
          }
          
          const cellIdx = XLSX.utils.encode_cell({r: row, c: col});
          const excelStyle = this.getExcelStyleFromHtml(htmlCell);
          if (excelStyle) {
            ws[cellIdx] = ws[cellIdx] || {t: 's',v: ''};
            ws[cellIdx].s = excelStyle;
          }
          
          for(let r=0;r<rowSpan;r++){
            const r2 = row+r;
            for(let c=0;c<colSpan;c++){
              const c2 = col+c;
              used[r2][c2] = htmlCell;
            }
          }
        }
      }
    }
    
    console.log(used);
    
    return ws;
  },
  getExcelStyleFromHtml(cell) {
    const style = {};
    const computedStyle = window.getComputedStyle(cell);
    
    const fontWeight = computedStyle.getPropertyValue('font-weight');
    if (fontWeight && (fontWeight === 'bold' || parseFloat(fontWeight) >= 700)) {
      style.font = style.font || {};
      style.font.bold = true;
    }
    
    const fontSize = computedStyle.getPropertyValue('font-size')?.slice(0,-2);
    if (fontSize) {
      style.font = style.font || {};
      const fontSizeValue = parseFloat(fontSize);
      const fontSizeUnit  = fontSize.match(/[a-z]+$/)?.[0] || 'px';
      switch(fontSizeUnit){
        case 'px':{
          //1pt ~ 1.333px
          style.font.sz = Math.round(fontSizeValue/1.333*10)/10;//A 1 decimal
        }break;
        case 'pt':{
          style.font.sz = fontSizeValue;
        }break;
        default:{//em, rem, etc
          const rootFontSize = parseFloat(
            window.getComputedStyle(document.body).getPropertyValue('font-size')
          );
          const pxSize = fontSizeValue*rootFontSize;
          style.font.sz = Math.round(pxSize/1.333*10)/10;//A 1 decimal
        }break;
      }
      
      style.font.sz = Math.min(Math.max(style.font.sz, 1), 409);//Limites de excel
    }
    
    const bgColor = computedStyle.getPropertyValue('background-color');
    if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
      style.fill = {
          patternType: "solid",
          fgColor: { rgb: this.hexToRgb(bgColor) || 'FFFFFF' }
      };
    }
    
    const textColor = computedStyle.getPropertyValue('color');
    if(textColor) {
      style.font = style.font || {};
      style.font.color = { rgb: this.hexToRgb(textColor) || '000000' };
    }
    
    const borders = ['right','bottom'];
    if(cell.closest('tr').querySelector('th','td') == cell){
      borders.push('left');
    }
    if(cell.closest('table').querySelector('th','td') == cell){
      borders.push('top');
    }
        
    for(const b of borders){
      const border = computedStyle.getPropertyValue(`border-${b}-width`);
      const color  = computedStyle.getPropertyValue(`border-${b}-color`);
      if(border && border !== '0px') {
        style.border = style.border || {};
        style.border[b] = { style: 'thin', color: { rgb: this.hexToRgb(color) || '000000' } };
      }
    }
    
    const textAlign = computedStyle.getPropertyValue('text-align');
    if(textAlign){
      style.alignment = style.alignment || {};
      style.alignment.horizontal = textAlign;
    }
    
    const verticalAlignCenter = cell.tagName == 'TH';
    if(verticalAlignCenter){
      style.alignment = style.alignment || {};
      style.alignment.vertical = 'center';
    }
    
    const overflowWrap = computedStyle.getPropertyValue('overflow-wrap');
    if(overflowWrap && (overflowWrap == 'break-word' || overflowWrap == 'anywhere')){
      style.alignment = style.alignment || {};
      style.alignment.wrapText = true;
    }
    
    return Object.keys(style).length > 0 ? style : undefined;
  },
  // Helper function to convert CSS color to Excel RGB format
  hexToRgb(cssColor) {
    // Handle rgb() format
    const rgbMatch = cssColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*\d+\.?\d*)?\)$/);
    if(rgbMatch) {
      const r = parseInt(rgbMatch[1]).toString(16).padStart(2, '0');
      const g = parseInt(rgbMatch[2]).toString(16).padStart(2, '0');
      const b = parseInt(rgbMatch[3]).toString(16).padStart(2, '0');
      return r + g + b;
    }
    
    // Handle hex format
    const hexMatch = cssColor.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/);
    if(hexMatch) {
      let hex = hexMatch[1];
      if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
      }
      return hex.toUpperCase();
    }
    
    return null;
  }
};
