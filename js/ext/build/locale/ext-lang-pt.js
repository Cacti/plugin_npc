/*
 * Portuguese Translation by AntonioCS
 */
Ext.View.prototype.emptyText = "";
Ext.grid.Grid.prototype.ddText = "%0 linha(s) selecionada(s)";
Ext.TabPanelItem.prototype.closeText = "Fechar Região";
Ext.form.Field.prototype.invalidText = "O valor para este campo é inválido";
Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Carregando...</div>';

Date.monthNames = [
   "Janeiro",
   "Fevereiro",
   "Março",
   "Abril",
   "Maio",
   "Junho",
   "Julho",
   "Agosto",
   "Setembro",
   "Outubro",
   "Novembro",
   "Dezembro"
];

Date.dayNames = [
   "Domingo",
   "Segunda",
   "Terça",
   "Quarta",
   "Quinta",
   "Sexta",
   "Sábado"
];

Ext.MessageBox.buttonText = {
   ok     : "OK",
   cancel : "Cancelar",
   yes    : "Sim",
   no     : "Não"
};

Ext.util.Format.date = function(v, format){
   if(!v) return "";
   if(!(v instanceof Date)) v = new Date(Date.parse(v));
   return v.dateFormat(format || "m/d/Y");
};

Ext.apply(Ext.DatePicker.prototype, {
   todayText         : "Hoje",
   minText           : "Esta data é anterior a menor data",
   maxText           : "Esta data é posterior a maior data",
   disabledDaysText  : "Não pode escolher este dia",
   disabledDatesText : "Não pode escolher esta data",
   nextText          : 'Próximo Mês (Control+Direito)',
   prevText          : 'Mês Posterior (Control+Esquerdo)',
   monthYearText     : 'Escolha um Mês (Control+Cima/Baixo para mover entre os anos)',
   todayTip          : "{0} (Espaço)",
   format            : "m/d/y"
});

Ext.apply(Ext.PagingToolbar.prototype, {
   beforePageText : "Página",
   afterPageText  : "de {0}",
   firstText      : "Primeira Página",
   prevText       : "Página Anterior",
   nextText       : "Próxima Página",
   lastText       : "Última Página",
   refreshText    : "Actualizar Listagem",
   displayMsg     : "<b>{0} a {1} de {2} registro(s)</b>",
   emptyMsg       : 'Sem registros para exibir'
});

Ext.apply(Ext.form.TextField.prototype, {
   minLengthText : "O tamanho mínimo permitido para este campo é {0}",
   maxLengthText : "O tamanho máximo para este campo é {0}",
   blankText     : "Este campo é obrigatório, por favor preencha.",
   regexText     : "",
   emptyText     : null
});

Ext.apply(Ext.form.NumberField.prototype, {
   minText : "O valor mínimo para este campo é {0}",
   maxText : "O valor máximo para este campo é {0}",
   nanText : "{0} não é um número válido"
});

Ext.apply(Ext.form.DateField.prototype, {
   disabledDaysText  : "Desabilitado",
   disabledDatesText : "Desabilitado",
   minText           : "A data deste campo deve ser posterior a {0}",
   maxText           : "A data deste campo deve ser anterior a {0}",
   invalidText       : "{0} não é uma data válida - deve ser informado no formato {1}",
   format            : "m/d/y"
});

Ext.apply(Ext.form.ComboBox.prototype, {
   loadingText       : "Carregando...",
   valueNotFoundText : undefined
});

Ext.apply(Ext.form.VTypes, {
   emailText    : 'Este campo deve ser um endereço de e-mail válido no formado "usuario@dominio.com"',
   urlText      : 'Este campo deve ser uma URL no formato "http:/'+'/www.dominio.com"',
   alphaText    : 'Este campo deve conter apenas letras e _',
   alphanumText : 'Este campo deve conter apenas letras, números e _'
});

Ext.apply(Ext.grid.GridView.prototype, {
   sortAscText  : "Ordenar Ascendente",
   sortDescText : "Ordenar Descendente",
   lockText     : "Bloquear Coluna",
   unlockText   : "Desbloquear Coluna",
   columnsText  : "Colunas"
});

Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
   nameText   : "Nome",
   valueText  : "Valor",
   dateFormat : "m/j/Y"
});

Ext.apply(Ext.SplitLayoutRegion.prototype, {
   splitTip            : "Arraste para redimencionar.",
   collapsibleSplitTip : "Arraste para redimencionar. Duplo clique para esconder."
});
