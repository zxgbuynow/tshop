$(document).ready(function () {
 alert(222)
    //展示货品数据
var goodsSpecTree = new Class({
    options: {
      speclist:'.speclist',
      specIMG: '.spec-img',
      specMap: '#dataNode',
      switchTrigger: '.typelist',
      switchContent: '.spec-wrap',
      number: '.product-number',
      specFillBtn: '.fill-action'
    },
    count: 0,
    init: function(container, options){
      this.setOptions(options);
      this.container = $(container);
      this.speclist = this.container.find(this.options.speclist);
      this.specLength = this.speclist.length;
      if(!this.specLength) return;
      this.specIMG = this.container.find(this.options.specIMG);
      this.specMap = $(this.options.specMap);
      this.specFillBtn = $(this.options.specFillBtn);
      this.switchTriggers = this.container.find(this.options.switchTrigger).children();
      this.switchPanels = this.container.find(this.options.switchContent).children();
      // this.selectAll = this.container.find('.selectAll input[type=checkbox]');
      this.number = this.container.find(this.options.number);
      this.newProduct = {};
      this.attach();
      // this.build(this.speclist);
      this.createAllGoods(false);
    },
    attach: function() {
      var self = this;
      for(var i = 0; i < this.specLength; i++) {
          (function(i) {
              var trigger = this.switchTriggers.eq(i),
                  panel = this.switchPanels.eq(i),
                  // sel = this.selectAll.eq(i),
                  list = this.speclist.eq(i);
              // trigger.on('click', function(e) {
              //     $(this).addClass('act').siblings('.act').removeClass('act');
              //     panel.show().siblings().hide();
              // });
              var chks = list.find('input[type=checkbox]');
              // sel.on('change', function(e) {
              //     chks.filter(function(){return !this.disabled;}).set('checked', this.checked);
              //     self.build(chks, list, i, trigger);
              // });
              chks.on('change', function(e) {
                  self.build(this, $(this).parents(self.options.speclist), i, trigger);
                  self.createAllGoods();
              });
              chks.on('click', function(e) {
                  self.toggleEdit(this, i);
              });

          }).call(this, i);
      }

      var checked = this.speclist.find('input:checked').eq(0);
      checked.trigger('change');

      this.container.on('change', '.edit-spec-name', function(e) {
          var str = this.name;
          var id = str.match(/\[([^\]]*)\]/)[1];

          self.specIMG.find('[data-id=' + id + ']').find('.spec-name').text(this.value);

          id = id.split('_');
          Spec[id[0]].option[id[1]].spec_value = this.value;
      })
      .on('click', '.clean:not(.disabled)', function(e) {
        var p = this.parent('tr');
        p.find('input[type=text], input[type=number]').val('');
        // p.find('input[type=checkbox]').val('false').prop('checked', false);
        var uid = this.attr('data-uid');
        $.each(self.products, function(i, p) {
          if(p.idx == uid) {
            self.products[i] = {
              idx: uid,
              sku_id: p.sku_id,
              spec: p.spec
            };
            // self.products.erase(p);
          }
        });
        var oldProduct = $.extend(true, {}, Products[uid]);
        Products[this.attr('data-uid')] = {
          sku_id: 'new',
          spec_desc: oldProduct.spec_desc
        };

        // delete Products[uid];
      });
      // this.specIMG.on('change', 'input[type=text], input[type=number]', function(e) {
      //   var str = this.name;
      //   var id = str.match(/\[([^\]]*)\]/)[1];
      //   id = id.split('_');
      //   Spec[id[0]].option[id[1]].spec_value = this.value;
      // });
      this.specMap
      // .on('blur', 'input[type=text], input[type=number]', function(e) {
      //     if(!self.validate(this)) {
      //       (function(){this.focus()}).delay(0, this);
      //       return false;
      //     }
      // })
      .on('change', 'input[type=text], input[type=number]', function(e) {
          var str = this.name;
          var uid = str.split('[')[0];
          var prop = str.match(/\[([^\]]*)\]/)[1];
          self.storeData(this, uid, prop);
      });
      //批量填充规格
      this.specFillBtn.on('click', function(){
        var specPrice = $.trim($('input[name="spec_price"]').val());
        var specMktPrice = $.trim($('input[name="spec_mkt_price"]').val());
        var specCostPrice = $.trim($('input[name="spec_cost_price"]').val());
        var specStore = $.trim($('input[name="spec_store"]').val());
        var specBn = $.trim($('input[name="spec_bn"]').val());
        var specBarcode = $.trim($('input[name="spec_barcode"]').val());

        if(specPrice != ''){
          $('.goods-spec').find('input[name$="[price]"]').each(function(){
            $(this).val(specPrice);
            $(this).trigger('change');
          });
          $('input[name="spec_price"]').val('');
        }
        if(specMktPrice != ''){
          $('.goods-spec').find('input[name$="[mkt_price]"]').each(function(){
            $(this).val(specMktPrice);
            $(this).trigger('change');
          });
          $('input[name="spec_mkt_price"]').val('');
        }
        if(specCostPrice != ''){
          $('.goods-spec').find('input[name$="[cost_price]"]').each(function(){
            $(this).val(specCostPrice);
            $(this).trigger('change');
          });
          $('input[name="spec_cost_price"]').val('');
        }
        if(specStore != ''){
          $('.goods-spec').find('input[name$="[store]"]').each(function(){
            $(this).val(specStore);
            $(this).trigger('change');
          });
          $('input[name="spec_store"]').val('');
        }
        if(specBn != ''){
          $('.goods-spec').find('input[name$="[bn]"]').each(function(){
            $(this).val(specBn);
            $(this).trigger('change');
          });
          $('input[name="spec_bn"]').val('');
        }
        if(specBarcode != ''){
          $('.goods-spec').find('input[name$="[barcode]"]').each(function(){
            $(this).val(specBarcode);
            $(this).trigger('change');
          });
          $('input[name="spec_barcode"]').val('');
        }
        setTotalStore();
      });
      $(document.body).on('click', '.save-action', function(e) {
        var keys = [], i = 0, j = self.products.length, k, l = self.props.length, p, attr, flag;
        for(; i < j; i++) {
          //flag = false;
          p = self.products[i];
          // if(!p.bn) {
          //   for(k = 0; k < l; k++) {
          //     attr = self.props[k];
          //     if(attr === 'sku_id' || attr === 'bn' || !p[attr] || p[attr] === 'false') {
          //       flag = true;
          //       continue;
          //     }
          //     alert('请填写"' + p.spec + '"商家编码。');
          //     try{
          //       self.specMap.find('input[name="' + p.idx + '[bn]"]').focus();
          //     } catch(e) {}
          //     return;
          //     // flag = 1;
          //     // break;
          //   }
          // }
          // if(flag === 1) return;
          //if(flag === true) continue;
          //else
          keys.push(p.idx);
        }
        // if(flag === 1) return;
        $.each(Spec, function(k, v) {
          $.each(v.option, function(m, l) {
            if(l.checked === false) delete Spec[k].option[m];
          });
        });
        var writecount = 0;
        var isprice = true;
        var isstore = true;
        var p_pricecount = 0;
        var p_storecount = 0;
        var new_pricecount = 0;
        var new_storecount = 0;
        $.each(Products, function(k, v){
          if(keys.indexOf(k) == -1) {
            delete Products[k];
          } else{ // if(Products[k].sku_id != "new")
            if((typeof(Products[k].price) == 'undefined' || typeof(Products[k].cost_price) == 'undefined' || typeof(Products[k].mkt_price) == 'undefined' || typeof(Products[k].store) == 'undefined') && Products[k].bn == "" && (Products[k].barcode == null || Products[k].barcode == "")) {
                delete Products[k];
            } else if(typeof(Products[k].bn) == 'undefined' && typeof(Products[k].barcode) == 'undefined' && Products[k].price == "" && Products[k].cost_price == "" && Products[k].mkt_price == "" && Products[k].store == "" ){
              delete Products[k];
            } else {
              if(Products[k].price != "" || Products[k].cost_price != "" || Products[k].mkt_price != "" || Products[k].store != "" || Products[k].bn != "" || (Products[k].barcode != null && Products[k].barcode != "")) {
                if(Products[k].price == "") {
                  isprice = false;
                }
                if(Products[k].store == "") {
                  isstore = false;
                }
                p_pricecount = Products[k].price == "" ? p_pricecount + 1 : p_pricecount > 0 ? p_pricecount - 1 : p_pricecount;
                p_storecount = Products[k].store == "" ? p_storecount + 1 : p_storecount > 0 ? p_storecount - 1 : p_storecount;
                writecount ++;
              } else {
                delete Products[k];
              }
            }
          }
        });

        $('#dataNode').find('tr[data-pid="new"]').each(function(){
          var goodsinfo_input = $(this).find('input[type="number"], input[type="text"]');
          var isflag = false;
          var empty_count = 0;
          goodsinfo_input.each(function(){
            if($(this).val() != "" && $(this).val() != null) {
              isflag = true;
            } else {
              empty_count ++;
            }
          });
          if(isflag) {
            writecount ++;
            if($(goodsinfo_input[0]).val() == "")  {
              isprice = false;
            }
            if($(goodsinfo_input[3]).val() == "") {
              isstore = false;
            }
            new_pricecount = $(goodsinfo_input[0]).val() == "" ? new_pricecount + 1 : new_pricecount > 0 ? new_pricecount - 1 : new_pricecount;
            new_storecount = $(goodsinfo_input[3]).val() == "" ? new_storecount + 1 : new_storecount > 0 ? new_storecount - 1 : new_storecount;
          } else if(empty_count == goodsinfo_input.length) {
            var obj = $(this).find('input[type="hidden"]').attr('name');
            obj = obj.substr(0, obj.indexOf('['));
            delete Products[obj];
          }
        });
        self.saveNewProduct(keys);
        if(!isnospec) {
          if(keys.length > 0 && (writecount <= 0)) {
            $('#messagebox').message('至少填写一种商品规格');
            return false;
          }
          if(!isprice || p_pricecount > 0 || new_pricecount > 0) {
            $('#messagebox').message('请填写商品规格对应的销售价格');
            return false;
          }
          if(!isstore || p_storecount > 0 || new_storecount > 0) {
            $('#messagebox').message('请填写商品规格对应的库存');
            return false;
          }
        }
        var max = $('input[name="item[store]"]').attr('data-max');
        var msg = '商品库存数量之和要小于等于' + max;
        if(Number($('input[name="item[store]"]').val()) > Number(max)) {
          if(isnospec) {
            msg = '库存数量要小于等于' + max;
            $('input[name="item[store]"]').focus();
          }
          $('#messagebox').message(msg);
          return false;
        }
      });
    },
    build: function(element, parent, i, trigger) {
      // var d = new Date();
      var id = parent.attr('data-spec-id');
      if(element.length) element.each(function(el){
        this.storeSpec(el, id, parent);
      }, this);
      else {
          this.storeSpec(element, id, parent);
      }
      // this.toggleEdit(element, i);
      trigger && trigger.find('i').text(parent.find('input[type=checkbox]:checked').length);
      this.createSpecGrid(parent, i, id);
      // this.createAllGoods();
    },
    toggleEdit: function(element) {
        var parent = $(element).parent();
        var specname = parent.find('.spec-name');
        var editSpecname = parent.find('.edit-spec-name');
        var editImg = parent.find('img');

        if(specname.hasClass('hide')) {
            specname.removeClass('hide');
            editSpecname.addClass('hide');
            editImg.removeClass('active');
        }
        else {
            specname.addClass('hide');
            editSpecname.removeClass('hide');
            editImg.addClass('active');
        }
    },
    storeSpec: function(el, id, parent) {
      var sid = el.value;
      if(el.checked) {
        if(!Spec[id]) {
          Spec[id] = {
            spec_name: parent.attr('data-spec-name'),
            spec_id: id,
            show_type: parent.attr('data-spec-type'),
            option: {}
          };
        }
        if(!Spec[id].option[sid]) {
          Spec[id].option[sid] = {
            // private_spec_value_id: el.name,
            spec_value: el.title,
            spec_value_id: sid
          };
          if(Spec[id].show_type == 'image') $.extend(Spec[id].option[sid], {
            spec_image: parent.find('input[key=spec_image_' + sid + ']').val(),
            spec_image_url: parent.find('img[key=spec_img_' + sid + ']').attr('src')
          });
        }
        else delete Spec[id].option[sid].checked;
      }
      else if(Spec[id]) {
        Spec[id].option[sid].checked = false;
      }
    },
    createAllGoods: function(needs) {
      var self = this;
      this.specElements = [];
      this.speclist.each(function(index) {
        var checkBox = $(this).find('input[type=checkbox]:checked');
        if(checkBox.length > 0) {
          self.specElements.push({
            id: $(this).attr('data-spec-id'),
            name: $(this).attr('data-spec-name'),
            input: checkBox
          });
        }
      });

      this.products = [];
      var length = this.specElements.length;
      if(length == this.specLength) {
        this.processProducts(this.specElements, 0, length);
      }
      else if(needs !== false) {
        return $('#messagebox').message('每个规格项至少选中一项，才能组合成完整的货品信息。');
      }
      this.number.text(this.products.length);
      this.createGoodsGrid();

      this.count = 0;
      if(this.products.length && goods_info.length) {
        this.products.some(function(v, i) {
          if(v.bn) {
            var n = v.bn.match(/^.+\-(\d+)$/);
            if(n && n[1]) {
              self.count = Math.max(self.count, Number(n[1]) + 1);
              return false;
            }
          }
          return true;
        });
      }
    },
    createSpecGrid: function(list, i, spec_id) {
        var spec = Spec[spec_id];
        var html = ['<thead>'];
        html.push('<tr>');
        html.push('<th>规格</th>');
        if(spec.show_type == 'image') html.push('<th>规格图片</th>');
        // html.push('<th>关联商品图片</th>');
        html.push('</tr>');
        html.push('</thead>');
        html.push('<tbody>');
        $.each(spec.option, function(k, v) {
          if(v.checked !== false) {
            html.push('<tr data-id="' + spec_id + '_' + v.spec_value_id + '">');
            html.push('<td>');
            if(spec.show_type == 'image') {
              html.push('<span class="spec-colorbox"><img src="' + list.find('img[key=spec_img_' + v.spec_value_id + ']').attr('src') + '"></span>');
            }
            html.push('<span class="spec-name">' + v.spec_value + '</span>');
            html.push('</td>');
            if(spec.show_type == 'image') html.push('<td><a class="select-image" data-toggle="modal" href="<{url action=topshop_ctl_shop_image@loadImageModal}>" data-target="#gallery_modal"><input type="hidden" name="images[' + spec_id + '_' + v.spec_value_id + ']" value="' + (v.spec_image || list.find('input[key=spec_image_' + v.spec_value_id + ']').val()) + '"><div class="img-put"><img src="' + (v.spec_image_url || list.find('img[key=spec_img_' + v.spec_value_id + ']').attr('src')) + '" /><i class="glyphicon glyphicon-picture"></i></div></a></td>');
            //if(spec.show_type == 'image') html.push('<td><div class="choose-image"><input type="file" class="hide action-file-input" name="upload_file" data-size="2097152" data-remote="<{url action=toputil_ctl_image@uploadImages from=shop}>" accept="image/*" /><span class="image-box action-upload"><input type="hidden" name="images[' + spec_id + '_' + v.spec_value_id + ']" value="' + (v.spec_image || list.find('input[key=spec_image_' + v.spec_value_id + ']').val()) + '"><img src="' + (v.spec_image_url || list.find('img[key=spec_img_' + v.spec_value_id + ']').attr('src')) + '" /></span><b class="choose-handle action-upload" title="选择图片"><i class="icon-arrow-right-b"></i></b></div></td>');
            html.push('</tr>');
          }
        })
        html.push('</tbody>');

        var table = $('<table>').addClass('table table-bordered').html(html.join('\n'));
        table.find('input[type=text]').prop('disabled', false);
        table.appendTo(this.specIMG.eq(i).empty());
        html = null;
    },
    props: ['sku_id', 'price','mkt_price','cost_price','store', 'bn', 'barcode', 'freez'],
    processProducts: function(arr, index, length, id, name, value, pvid) {
      var self = this, specid = {}, spec_name = [arr[index].name], specvalue = {}, specpvid={}, sname, spec_id = arr[index].id, uid;
      if(name) {
        spec_name = name.concat(spec_name);
      }
      if(value) specvalue = value;
      // if(id) specid = id;
      arr[index].input.each(function(i){
        specid[spec_id] = this.value;
        specpvid[spec_id] = this.name;
        specvalue[spec_id] = Spec[spec_id].option[this.value].spec_value;
        if(id) {
          $.extend(specid, id);
        }
        if(pvid) {
          $.extend(specpvid, pvid);
        }
        if(index < length - 1) {
          self.processProducts(arr, index + 1, length, specid, spec_name, specvalue, specpvid);
        } else if(index == length - 1) {
          var specid_values = [];
          for(var key in specid) {
            specid_values.push(specid[key]);
          }
          uid = getUniqueID(specid_values.join(';'));
          sname = [];
          $.each(spec_name, function(j) {
            spec_id = arr[j].id;
            if(Products[uid] && Products[uid].spec_desc) Products[uid].spec_desc.spec_value[spec_id] = specvalue[spec_id];
            sname.push(this + ':' + specvalue[spec_id]);
          });
          sname = sname.join('，');
          self.mapping(uid, sname, specvalue, specid, specpvid);
        }
      });
    },
    mapping: function(uid, specname, specvalue, specid, specpvid) {
      var arr = {};
      var self = this;
      $.each(this.props, function(i) {
        if(Products[uid] && (Products[uid][this] || Products[uid][this] === 0)) {
          arr[this] = Products[uid][this];
        }
        else if(goods_info[this]) {
          arr[this] = goods_info[this];
          if(this == 'sku_id') {
            arr[this] = 'new';
          }
          else if (this == 'bn') {
            arr[this] = arr[this] + '-' + self.count;
            self.count ++;
          }
        }
      if(this == 'sku_id' && !arr[this]) {
        arr[this] = 'new';
      }
      });
      if(!Products[uid]) {
        this.newProduct[uid] = $.extend(true, {}, arr);
        this.newProduct[uid].spec_desc = {};
        // this.newProduct[uid].spec_desc.spec_private_value_id = $.extend(true, {}, specpvid);
        this.newProduct[uid].spec_desc.spec_value = $.extend(true, {}, specvalue);
        this.newProduct[uid].spec_desc.spec_value_id = $.extend(true, {}, specid);
      }

      if(Products[uid] && activeProducts && activeProducts.length && activeProducts.indexOf(Products[uid].sku_id) > -1) {
        arr.unavailable = 'disabled';
        arr.title = '尚有未处理的订单，不能清除此货品。';
      }
      else {
        arr.title = '不生成此货品';
      }
      arr.idx = uid;
      if(specname) arr.spec = specname;
      this.products.push(arr);
    },
    createGoodsGrid: function() {
      this.specMap.data('instance', this);
      // if(!arr) return this.specMap.erase('html');
      var current = this.container.find('.current');
      current = current.size() ? parseInt(current.text()) : 1;
      this.pager = new Pager(this.specMap, this.products, {current: current, paging: 10});
      // $('#specification').Validator('addField', this.specMap.find('.price, .store'));
      getTotalStore();
    },
    saveNewProduct: function(uid) {
      var self = this;
      if($.type(uid) === 'array' && uid.length) {
        if(!$.isEmptyObject(this.newProduct)) {
          $.each(this.newProduct, function(k) {
            if(uid.indexOf(k) == -1) {
              delete self.newProduct[k];
            }
          });
        }
      }
      else if(uid) {
        if(!Products[uid]) {
          Products[uid] = $.extend(true, {}, this.newProduct[uid]);
          delete this.newProduct[uid];
        }
        return;
      }
      if(!$.isEmptyObject(goods_info) && !$.isEmptyObject(this.newProduct)) {
        $.each(this.newProduct, function(k, v) {
          if(v) Products[k] = $.extend(true, {}, v);
        });
        this.newProduct = {};
      }
    },
    storeData: function(el, uid, prop, sib) {
      this.saveNewProduct(uid);
      Products[uid][prop] = encodeURIComponent(el.value);

      if(!$.isEmptyObject(goods_info)) {
        var n = Products[uid][prop].match(/^.+\-(\d+)$/);
        if(n && n[1]) {
          this.count = Math.max(this.count, Number(n[1]) + 1);
        }
      }

      for(var i = 0, j = this.products.length, k; i < j; i++) {
        k = this.products[i];
        if(k.idx === uid) {
          k[prop] = el.value;
          break;
        }
      }
    },
    validate: function(el) {
      return $(el).Validator();
    }
});
});