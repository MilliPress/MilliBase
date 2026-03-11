/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/components/FieldRenderer.jsx"
/*!************************************************!*\
  !*** ./assets/js/components/FieldRenderer.jsx ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _fields_TextField_jsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./fields/TextField.jsx */ "./assets/js/components/fields/TextField.jsx");
/* harmony import */ var _fields_NumberField_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./fields/NumberField.jsx */ "./assets/js/components/fields/NumberField.jsx");
/* harmony import */ var _fields_PasswordField_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./fields/PasswordField.jsx */ "./assets/js/components/fields/PasswordField.jsx");
/* harmony import */ var _fields_ToggleField_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./fields/ToggleField.jsx */ "./assets/js/components/fields/ToggleField.jsx");
/* harmony import */ var _fields_SelectField_jsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./fields/SelectField.jsx */ "./assets/js/components/fields/SelectField.jsx");
/* harmony import */ var _fields_UnitField_jsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./fields/UnitField.jsx */ "./assets/js/components/fields/UnitField.jsx");
/* harmony import */ var _fields_TokenListField_jsx__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./fields/TokenListField.jsx */ "./assets/js/components/fields/TokenListField.jsx");
/* harmony import */ var _fields_ColorField_jsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./fields/ColorField.jsx */ "./assets/js/components/fields/ColorField.jsx");
/* harmony import */ var _fields_CodeField_jsx__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./fields/CodeField.jsx */ "./assets/js/components/fields/CodeField.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__);
/**
 * Maps field.type to the appropriate component.
 * Supports built-in types and custom types registered via registerFieldType().
 */











const builtinTypes = {
  text: _fields_TextField_jsx__WEBPACK_IMPORTED_MODULE_0__["default"],
  number: _fields_NumberField_jsx__WEBPACK_IMPORTED_MODULE_1__["default"],
  password: _fields_PasswordField_jsx__WEBPACK_IMPORTED_MODULE_2__["default"],
  toggle: _fields_ToggleField_jsx__WEBPACK_IMPORTED_MODULE_3__["default"],
  select: _fields_SelectField_jsx__WEBPACK_IMPORTED_MODULE_4__["default"],
  unit: _fields_UnitField_jsx__WEBPACK_IMPORTED_MODULE_5__["default"],
  'token-list': _fields_TokenListField_jsx__WEBPACK_IMPORTED_MODULE_6__["default"],
  color: _fields_ColorField_jsx__WEBPACK_IMPORTED_MODULE_7__["default"],
  code: _fields_CodeField_jsx__WEBPACK_IMPORTED_MODULE_8__["default"]
};
const FieldRenderer = ({
  field,
  value,
  onChange,
  disabled
}) => {
  // Check for custom field types first.
  const customTypes = window.MilliBase?.customFieldTypes || {};
  const Component = customTypes[field.type] || builtinTypes[field.type];
  if (!Component) {
    return null;
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)(Component, {
    field: field,
    value: value,
    onChange: onChange,
    disabled: disabled
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FieldRenderer);

/***/ },

/***/ "./assets/js/components/Header.jsx"
/*!*****************************************!*\
  !*** ./assets/js/components/Header.jsx ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/backup.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/flip-vertical.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/lifesaver.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/more-vertical.mjs");
/* harmony import */ var _SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./SettingsProvider.jsx */ "./assets/js/components/SettingsProvider.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__);
/**
 * Header component: title, links, save button, custom buttons, actions dropdown, progress bar.
 * Fully driven by the PHP schema's `header` config.
 */







const iconMap = {
  lifesaver: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__["default"],
  backup: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__["default"],
  flipVertical: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["default"]
};
const Header = () => {
  const {
    config,
    status,
    saveSettings,
    isSaving,
    isLoading,
    hasChanges,
    triggerAction
  } = (0,_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_7__.useSettings)();
  const header = config.header || {};
  const links = header.links || [];
  const buttons = header.buttons || [];
  const menuItems = header.menu_items || [];

  // Track which custom button modals are open.
  const [openModals, setOpenModals] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const renderCustomButton = (btn, idx) => {
    // If button has a registered component, render it.
    if (btn.component) {
      const CustomBtn = window.MilliBase?.customComponents?.[btn.component];
      if (CustomBtn) {
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomBtn, {
          key: idx,
          status,
          triggerAction,
          isSaving,
          isLoading
        });
      }
    }
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
      __next40pxDefaultSize: true,
      variant: btn.variant || 'secondary',
      onClick: () => {
        if (btn.action) {
          triggerAction(btn.action);
        }
      },
      disabled: isSaving || isLoading,
      children: btn.label
    }, idx);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
      className: "millibase-header",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Flex, {
        align: "center",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FlexItem, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("h1", {
            style: {
              padding: '0'
            },
            children: header.title || ''
          }), links.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Flex, {
            expanded: "false",
            justify: "start",
            children: links.map((link, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ExternalLink, {
                className: "external-link",
                href: link.url,
                children: link.label
              })
            }, i))
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FlexItem, {
          align: "end",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
            __next40pxDefaultSize: true,
            style: {
              marginRight: '10px'
            },
            isBusy: isSaving,
            isPrimary: true,
            onClick: saveSettings,
            disabled: !hasChanges || isSaving,
            children: isSaving ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Saving…', 'millibase') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Save Settings', 'millibase')
          }), buttons.map((btn, idx) => renderCustomButton(btn, idx)), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Dropdown, {
            className: "millibase-actions-dropdown",
            contentClassName: "millibase-actions-dropdown-content",
            popoverProps: {
              placement: 'bottom-end'
            },
            renderToggle: ({
              isOpen,
              onToggle
            }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
              __next40pxDefaultSize: true,
              icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__["default"],
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('More Actions', 'millibase'),
              disabled: isSaving || isLoading,
              onClick: onToggle,
              "aria-expanded": isOpen
            }),
            renderContent: ({
              onClose
            }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.MenuGroup, {
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('More Actions', 'millibase'),
              children: [menuItems.map((item, idx) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.MenuItem, {
                __next40pxDefaultSize: true,
                icon: iconMap[item.icon] || null,
                iconPosition: "left",
                onClick: () => {
                  onClose();
                  if (item.url) {
                    window.open(item.url, '_blank');
                  } else if (item.action) {
                    triggerAction(item.action);
                  }
                },
                children: item.label
              }, idx)), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.MenuItem, {
                __next40pxDefaultSize: true,
                icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["default"],
                iconPosition: "left",
                onClick: () => {
                  onClose();
                  triggerAction('reset');
                },
                disabled: status.settings?.has_defaults,
                children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Reset all Settings', 'millibase')
              }), status.settings?.has_backup && status.settings?.has_defaults && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.MenuItem, {
                __next40pxDefaultSize: true,
                icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__["default"],
                iconPosition: "left",
                onClick: () => {
                  onClose();
                  triggerAction('restore');
                },
                children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Restore previous Settings', 'millibase')
              })]
            })
          })]
        })]
      })
    }), (isLoading || isSaving) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Animate, {
      type: "slide-in",
      options: {
        origin: 'top center'
      },
      children: ({
        className
      }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ProgressBar, {
        className: `millibase-progress ${className}`
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

/***/ },

/***/ "./assets/js/components/LabelWithTooltip.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/LabelWithTooltip.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LabelWithTooltip: () => (/* binding */ LabelWithTooltip)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/icon/index.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/help.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);



/**
 * Renders a label with a tooltip help icon.
 *
 * @param {Object}              props              Component properties.
 * @param {string|React.ReactNode} props.label     The label text.
 * @param {string}              props.tooltip       Tooltip text.
 * @param {number}              [props.iconSize=16] Help icon size.
 * @param {string}              [props.justify]     Flex justification.
 * @param {Object}              [props.style]       Additional styles.
 * @param {Object}              [props.tooltipProps] Tooltip props.
 * @param {Object}              [props.iconProps]   Icon props.
 * @return {React.ReactElement}
 */

const LabelWithTooltip = ({
  label,
  tooltip,
  iconSize = 16,
  justify = 'flex-start',
  style = {},
  tooltipProps = {},
  iconProps = {}
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
  align: "center",
  gap: 1,
  style: style,
  justify: justify,
  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
    children: label
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Tooltip, {
    text: tooltip,
    delay: "250",
    style: {
      maxWidth: '300px'
    },
    ...tooltipProps,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "millibase-tooltip-icon",
      style: {
        display: 'flex',
        alignItems: 'center'
      },
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_1__["default"], {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__["default"],
        size: iconSize,
        ...iconProps
      })
    })
  })]
});

/***/ },

/***/ "./assets/js/components/SectionRenderer.jsx"
/*!**************************************************!*\
  !*** ./assets/js/components/SectionRenderer.jsx ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _FieldRenderer_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FieldRenderer.jsx */ "./assets/js/components/FieldRenderer.jsx");
/* harmony import */ var _SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsProvider.jsx */ "./assets/js/components/SettingsProvider.jsx");
/* harmony import */ var _utils_evaluateCondition_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/evaluateCondition.js */ "./assets/js/utils/evaluateCondition.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);
/**
 * Renders a PanelBody with grouped fields from a section definition.
 */







/**
 * Group fields into rows based on the `inline` flag.
 *
 * A field without `inline` starts a new row.
 * A field with `inline: true` joins the previous row.
 *
 * Returns an array where each entry is an array of one or more fields.
 */

const groupFieldsIntoRows = fields => {
  const rows = [];
  for (const field of fields) {
    if (field.inline && rows.length > 0) {
      rows[rows.length - 1].push(field);
    } else {
      rows.push([field]);
    }
  }
  return rows;
};
const SectionRenderer = ({
  section
}) => {
  const context = (0,_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__.useSettings)();
  const {
    status,
    settings,
    updateSetting
  } = context;
  const constants = status?.settings?.constants || {};

  // Active-toggle configuration.
  const active = section.active || null;
  let activeModule, activeKey, isActive;
  if (active) {
    const activeParts = active.key.split('.');
    activeModule = activeParts[0];
    activeKey = activeParts[1];
    isActive = settings?.[activeModule]?.[activeKey] ?? active.default;
  }
  const renderField = field => {
    const parts = field.key.split('.');
    const module = parts[0];
    const key = parts[1];
    const constantDisabled = settings?.[module] ? !(key in settings[module]) : false;

    // Fields are disabled when defined by a constant OR when
    // the section's active toggle is off.
    const disabled = constantDisabled || active && !isActive;

    // For constant-defined fields, show the constant value
    // from the status API instead of the schema default.
    const value = constantDisabled ? constants?.[module]?.[key] ?? field.default : settings?.[module]?.[key] ?? field.default;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_FieldRenderer_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
      field: field,
      value: value,
      onChange: newValue => updateSetting(module, key, newValue),
      disabled: disabled
    }, field.key);
  };
  const isFieldVisible = field => {
    // Merge editable settings with constant overrides so that
    // hide/show conditions reflect the effective runtime values.
    const effective = {
      ...settings
    };
    for (const [mod, vals] of Object.entries(constants)) {
      effective[mod] = {
        ...effective[mod],
        ...vals
      };
    }
    if (field.hide && (0,_utils_evaluateCondition_js__WEBPACK_IMPORTED_MODULE_4__["default"])(field.hide, effective)) {
      return false;
    }
    if (field.show && !(0,_utils_evaluateCondition_js__WEBPACK_IMPORTED_MODULE_4__["default"])(field.show, effective)) {
      return false;
    }
    return true;
  };
  const visibleFields = (section.fields || []).filter(isFieldVisible);
  const rows = groupFieldsIntoRows(visibleFields);
  // Status evaluation.
  const statusConfig = section.status;
  const hasStatus = statusConfig?.key != null;
  const isOk = hasStatus ? (0,_utils_evaluateCondition_js__WEBPACK_IMPORTED_MODULE_4__.resolveDotPath)(status, statusConfig.key) === statusConfig.ok : true;
  const statusColor = isOk ? '#00a32a' : '#d63638';

  // Active-toggle element for section header.
  const activeToggleElement = active ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
    onClick: e => e.stopPropagation(),
    onKeyDown: e => e.stopPropagation(),
    role: "presentation",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.FormToggle, {
      checked: isActive,
      onChange: () => {
        const next = !isActive;
        updateSetting(activeModule, activeKey, next);
        if (next) {
          setIsOpen(true);
        }
      }
    })
  }) : null;

  // Build a custom title element when status or active toggle is configured.
  const title = hasStatus || active ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px',
      width: '100%'
    },
    children: [activeToggleElement, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
      children: section.title
    }), hasStatus && statusConfig.badge && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
      style: {
        fontSize: '11px',
        lineHeight: '1',
        padding: '4px 8px',
        borderRadius: '9999px',
        backgroundColor: isOk ? '#e3f5e1' : '#fcecec',
        color: statusColor,
        fontWeight: 500
      },
      children: isOk ? statusConfig.badge.ok : statusConfig.badge.error
    })]
  }) : section.title;

  // Panel open/close logic.
  const openPref = section.open;
  let initialOpen;
  if (openPref === 'error') {
    initialOpen = !isOk;
  } else if (openPref === 'ok') {
    initialOpen = isOk;
  } else {
    initialOpen = openPref !== false;
  }

  // Controlled state for sections with active toggle — auto-opens
  // the panel when the toggle is switched on.
  const [isOpen, setIsOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialOpen);
  const renderContent = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
    children: [section.intro && (() => {
      const CustomDesc = window.MilliBase?.customComponents?.[section.intro];
      return CustomDesc ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CustomDesc, context) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
        className: "millibase-section-intro",
        children: section.intro
      });
    })(), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Flex, {
      direction: "column",
      gap: "4",
      children: rows.map(row => {
        if (row.length === 1) {
          return renderField(row[0]);
        }
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Flex, {
          justify: "start",
          align: "flex-start",
          gap: "4",
          children: row.map(field => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.FlexItem, {
            isBlock: !field.width,
            style: field.width ? {
              width: field.width
            } : undefined,
            children: renderField(field)
          }, field.key))
        }, row.map(f => f.key).join('-'));
      })
    })]
  });

  // Sections with an active toggle use controlled PanelBody so we can
  // auto-open on activation. Others stay uncontrolled.
  const panelProps = active ? {
    opened: isOpen,
    onToggle: () => setIsOpen(!isOpen)
  } : {
    initialOpen
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
    title: title,
    ...panelProps,
    className: active && !isActive ? 'millibase-section-disabled' : undefined,
    children: renderContent()
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SectionRenderer);

/***/ },

/***/ "./assets/js/components/SettingsApp.jsx"
/*!**********************************************!*\
  !*** ./assets/js/components/SettingsApp.jsx ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/caution.mjs");
/* harmony import */ var _SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsProvider.jsx */ "./assets/js/components/SettingsProvider.jsx");
/* harmony import */ var _Header_jsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Header.jsx */ "./assets/js/components/Header.jsx");
/* harmony import */ var _TabRenderer_jsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./TabRenderer.jsx */ "./assets/js/components/TabRenderer.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);
/**
 * Top-level SettingsApp: loading, error, tabs.
 * Reads config from props (injected by the auto-mount in millibase.js).
 */








const ErrorDisplay = ({
  error,
  onRetry,
  isRetrying,
  troubleshooting
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
  className: "millibase-error-container",
  style: {
    padding: '60px 20px',
    textAlign: 'center',
    maxWidth: '600px',
    margin: '0 auto'
  },
  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
    style: {
      marginBottom: '24px'
    },
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Icon, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__["default"],
      size: 48,
      style: {
        color: '#dc3232',
        opacity: 0.8
      }
    })
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h2", {
    style: {
      margin: '0 0 16px 0',
      fontSize: '24px',
      fontWeight: '600',
      color: '#1e1e1e'
    },
    children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Connection Error', 'millibase')
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
    style: {
      fontSize: '16px',
      lineHeight: '1.5',
      color: '#646970',
      maxWidth: '500px',
      margin: '0 auto 32px auto'
    },
    children: error
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
    style: {
      marginBottom: '32px',
      display: 'flex',
      justifyContent: 'center',
      gap: '12px',
      flexWrap: 'wrap'
    },
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      variant: "primary",
      onClick: onRetry,
      isBusy: isRetrying,
      disabled: isRetrying,
      children: isRetrying ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Retrying...', 'millibase') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Try Again', 'millibase')
    })
  }), troubleshooting?.url && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    style: {
      borderTop: '1px solid #e0e0e0',
      paddingTop: '24px',
      color: '#646970',
      fontSize: '14px'
    },
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
      style: {
        margin: '0 0 12px 0'
      },
      children: troubleshooting.text || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Need help fixing this issue?', 'millibase')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      href: troubleshooting.url,
      target: "_blank",
      variant: "tertiary",
      size: "compact",
      style: {
        margin: '0'
      },
      children: [troubleshooting.label || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('View Troubleshooting Guide', 'millibase'), ' →']
    })]
  })]
});
const SettingsApp = ({
  config
}) => {
  const {
    error,
    isLoading,
    activeTab,
    setActiveTab,
    retryConnection,
    isRetrying
  } = (0,_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__.useSettings)();
  const tabs = (config.schema?.tabs || []).map(tab => ({
    name: tab.name,
    title: tab.title,
    ...tab
  }));

  // Set initial tab if not already set.
  const initialTab = activeTab || (tabs[0]?.name ?? 'settings');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "millibase-settings-wrapper",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_Header_jsx__WEBPACK_IMPORTED_MODULE_4__["default"], {}), (() => {
      if (isLoading) {
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top center'
          },
          children: ({}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
            className: "millibase-loading-container",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
              style: {
                textAlign: 'center',
                margin: '0',
                padding: '15px 20px',
                borderBottom: '1px solid #e0e0e0',
                fontWeight: '500'
              },
              children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading settings...', 'millibase')
            })
          })
        });
      }
      if (error) {
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(ErrorDisplay, {
          error: error,
          onRetry: retryConnection,
          isRetrying: isRetrying,
          troubleshooting: config.troubleshooting
        });
      }
      if (tabs.length === 0) {
        return null;
      }
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: ({
          className
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TabPanel, {
          className: `millibase-tabs ${className}`,
          style: {
            border: '1px solid #ddd',
            marginLeft: '-1px',
            marginRight: '-1px'
          },
          initialTabName: initialTab,
          onSelect: tabName => setActiveTab(tabName),
          tabs: tabs,
          children: tab => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
            className: "millibase-tab-content",
            style: {
              margin: '-1px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_TabRenderer_jsx__WEBPACK_IMPORTED_MODULE_5__["default"], {
              tab: tab
            })
          })
        })
      });
    })()]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SettingsApp);

/***/ },

/***/ "./assets/js/components/SettingsProvider.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/SettingsProvider.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SettingsProvider: () => (/* binding */ SettingsProvider),
/* harmony export */   useSettings: () => (/* binding */ useSettings)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_sanitize__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/sanitize */ "@wordpress/sanitize");
/* harmony import */ var _wordpress_sanitize__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_sanitize__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _SnackbarProvider_jsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SnackbarProvider.jsx */ "./assets/js/components/SnackbarProvider.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const SettingsContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)();
const SettingsProvider = ({
  config,
  children
}) => {
  const {
    optionName,
    restNamespace
  } = config;
  const [status, setStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [initialSettings, setInitialSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const [isSaving, setIsSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [hasChanges, setHasChanges] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [hasStorageChanges, setHasStorageChanges] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [activeTab, setActiveTab] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [isRetrying, setIsRetrying] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const statusIntervalRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const {
    showSnackbar
  } = (0,_SnackbarProvider_jsx__WEBPACK_IMPORTED_MODULE_4__.useSnackbar)();
  const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
  const handleApiError = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(apiError => {
    let message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('An unexpected error occurred.', 'millibase');
    if (apiError?.message) {
      message = apiError.message;
    } else if (apiError?.code) {
      switch (apiError.code) {
        case 'rest_no_route':
          message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('API endpoint not found.', 'millibase');
          break;
        case 'rest_forbidden':
          message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Access denied.', 'millibase');
          break;
        case 'rest_cookie_invalid_nonce':
          message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Security check failed. Please refresh.', 'millibase');
          break;
        default:
          message = apiError.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('API request failed.', 'millibase');
      }
    }
    return typeof message === 'string' ? (0,_wordpress_sanitize__WEBPACK_IMPORTED_MODULE_2__.stripTags)(message) : message;
  }, []);
  const apiRequest = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async options => {
    try {
      await delay(300);
      return await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()(options);
    } catch (apiError) {
      const errorMessage = handleApiError(apiError);
      throw new Error(errorMessage);
    }
  }, [handleApiError]);
  const triggerAction = async (action, data = {}) => {
    setIsLoading(true);
    try {
      // Determine endpoint: check if it matches a custom action.
      let path = `/${restNamespace}/settings`;
      const customAction = (config.actions || []).find(a => a.name === action);
      if (customAction) {
        path = `/${restNamespace}/${customAction.endpoint}`;
      }
      const response = await apiRequest({
        path,
        method: 'POST',
        data: {
          action,
          ...data
        }
      });
      await delay(800);
      if (response.success) {
        showSnackbar(response.message);
        fetchSettings();
        fetchStatus();
      } else {
        throw new Error(response.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Action failed', 'millibase'));
      }
    } catch (actionError) {
      const errorText = actionError.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Action failed', 'millibase');
      showSnackbar(errorText, [], 6000, true);
      throw actionError;
    } finally {
      setIsLoading(false);
    }
  };
  const fetchStatus = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    try {
      const response = await apiRequest({
        path: `/${restNamespace}/status`,
        method: 'GET'
      });
      setStatus(response);
      setError(null);
      return response;
    } catch (fetchError) {
      const errorMessage = fetchError.message;
      setStatus({
        connected: false,
        error: errorMessage
      });
      setError(errorMessage);
      return errorMessage;
    }
  }, [apiRequest, restNamespace]);
  const fetchSettings = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    try {
      setIsLoading(true);
      const response = await apiRequest({
        path: '/wp/v2/settings'
      });
      setSettings(response?.[optionName]);
      setInitialSettings(response?.[optionName]);
      setError(null);
    } catch (fetchError) {
      setError(fetchError.message);
    } finally {
      setIsLoading(false);
    }
  }, [apiRequest, optionName]);
  const retryConnection = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    setIsRetrying(true);
    setError(null);
    try {
      await Promise.all([fetchSettings(), fetchStatus()]);
    } finally {
      setIsRetrying(false);
    }
  }, [fetchSettings, fetchStatus]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchSettings();
    fetchStatus();
    if (statusIntervalRef.current) {
      clearInterval(statusIntervalRef.current);
    }
    statusIntervalRef.current = setInterval(() => {
      if (!error) {
        fetchStatus();
      }
    }, 15000);
    return () => {
      if (statusIntervalRef.current) {
        clearInterval(statusIntervalRef.current);
      }
    };
  }, [fetchSettings, fetchStatus, error]);
  const updateSetting = (module, key, value) => {
    setSettings(prev => {
      const updated = {
        ...prev,
        [module]: {
          ...prev[module],
          [key]: value
        }
      };
      setHasChanges(JSON.stringify(updated) !== JSON.stringify(initialSettings));
      if (module === 'storage') {
        setHasStorageChanges(true);
      }
      return updated;
    });
  };
  const saveSettings = async () => {
    if (!hasChanges) {
      return;
    }
    try {
      setIsSaving(true);
      await apiRequest({
        path: '/wp/v2/settings',
        method: 'POST',
        data: {
          [optionName]: settings
        }
      });
      setInitialSettings(settings);
      showSnackbar((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Settings saved successfully.', 'millibase'));
      setHasChanges(false);
      if (hasStorageChanges) {
        const previousStatus = {
          ...status
        };
        await delay(500);
        showSnackbar((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Storage settings updated. Testing connection…', 'millibase'));
        await delay(3000);
        const newStatus = await fetchStatus();
        if (newStatus && previousStatus) {
          if (previousStatus.storage?.connected && !newStatus.storage?.connected) {
            await delay(50);
            showSnackbar((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Storage connection lost.', 'millibase'));
          } else if (!previousStatus.storage?.connected && newStatus.storage?.connected) {
            showSnackbar((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Storage connection established.', 'millibase'));
          }
          if (newStatus.storage?.error) {
            showSnackbar(newStatus.storage.error, [], 6000, true);
          }
        }
        setHasStorageChanges(false);
      }
    } catch (saveError) {
      const errorMessage = saveError.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Failed to save settings.', 'millibase');
      showSnackbar(errorMessage, [], 6000, true);
    } finally {
      setTimeout(() => setIsSaving(false), 1200);
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(SettingsContext.Provider, {
    value: {
      config,
      status,
      settings,
      error,
      isLoading,
      isSaving,
      hasChanges,
      updateSetting,
      saveSettings,
      triggerAction,
      activeTab,
      setActiveTab,
      retryConnection,
      isRetrying
    },
    children: children
  });
};
const useSettings = () => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(SettingsContext);
};

/***/ },

/***/ "./assets/js/components/SnackbarProvider.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/SnackbarProvider.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SnackbarProvider: () => (/* binding */ SnackbarProvider),
/* harmony export */   useSnackbar: () => (/* binding */ useSnackbar)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const SnackbarContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)();
const SnackbarProvider = ({
  slug,
  children
}) => {
  const [snackMessages, setSnackMessages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const showSnackbar = (message, actions = [], timeout = 3000, explicitDismiss = false) => {
    const id = Math.random().toString(36).slice(2, 11);
    setSnackMessages(prev => [...prev, {
      id,
      content: message,
      actions,
      explicitDismiss,
      spokenMessage: message
    }]);
    setTimeout(() => {
      hideSnackbar(id);
    }, timeout);
  };
  const hideSnackbar = id => {
    setSnackMessages(prev => prev.filter(msg => msg.id !== id));
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(SnackbarContext.Provider, {
    value: {
      showSnackbar
    },
    children: [children, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SnackbarList, {
      className: "millibase-snacks",
      notices: snackMessages,
      onRemove: id => hideSnackbar(id)
    })]
  });
};
const useSnackbar = () => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(SnackbarContext);
};

/***/ },

/***/ "./assets/js/components/TabRenderer.jsx"
/*!**********************************************!*\
  !*** ./assets/js/components/TabRenderer.jsx ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _SectionRenderer_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SectionRenderer.jsx */ "./assets/js/components/SectionRenderer.jsx");
/* harmony import */ var _SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsProvider.jsx */ "./assets/js/components/SettingsProvider.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
/**
 * Renders tab content from schema.
 * Supports sections with fields, and custom component tabs.
 */






const TabRenderer = ({
  tab
}) => {
  const context = (0,_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_3__.useSettings)();

  // Custom component tab.
  if (tab.type === 'custom' && tab.component) {
    const CustomComponent = window.MilliBase?.customComponents?.[tab.component];
    if (CustomComponent) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CustomComponent, {
        status: context.status,
        settings: context.settings,
        triggerAction: context.triggerAction,
        isLoading: context.isLoading
      });
    }
    return null;
  }

  // Standard sections tab.
  if (tab.sections) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Panel, {
      children: [tab.intro && (() => {
        const CustomDesc = window.MilliBase?.customComponents?.[tab.intro];
        return CustomDesc ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CustomDesc, context) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelRow, {
            children: tab.intro
          })
        });
      })(), tab.sections.map(section => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_SectionRenderer_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
        section: section
      }, section.id))]
    });
  }
  return null;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TabRenderer);

/***/ },

/***/ "./assets/js/components/fields/CodeField.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/fields/CodeField.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const CodeField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  value: value ?? '',
  disabled: disabled,
  onChange: onChange,
  rows: field.rows || 6,
  className: "millibase-code-field"
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (CodeField);

/***/ },

/***/ "./assets/js/components/fields/ColorField.jsx"
/*!****************************************************!*\
  !*** ./assets/js/components/fields/ColorField.jsx ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const ColorField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
  children: [field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
    children: field.label
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ColorPicker, {
    color: value || '#000000',
    onChange: onChange,
    enableAlpha: field.enableAlpha ?? false
  })]
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ColorField);

/***/ },

/***/ "./assets/js/components/fields/NumberField.jsx"
/*!*****************************************************!*\
  !*** ./assets/js/components/fields/NumberField.jsx ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const NumberField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNumberControl, {
  __next40pxDefaultSize: true,
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  value: value ?? 0,
  disabled: disabled,
  min: field.min,
  max: field.max,
  onChange: onChange
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (NumberField);

/***/ },

/***/ "./assets/js/components/fields/PasswordField.jsx"
/*!*******************************************************!*\
  !*** ./assets/js/components/fields/PasswordField.jsx ***!
  \*******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const PasswordField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalInputControl, {
  __next40pxDefaultSize: true,
  type: "password",
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  value: value ?? '',
  disabled: disabled,
  onChange: onChange
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PasswordField);

/***/ },

/***/ "./assets/js/components/fields/SelectField.jsx"
/*!*****************************************************!*\
  !*** ./assets/js/components/fields/SelectField.jsx ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const SelectField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
  __next40pxDefaultSize: true,
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  value: value ?? '',
  disabled: disabled,
  onChange: onChange,
  options: (field.options || []).map(opt => ({
    label: opt.label,
    value: opt.value
  }))
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SelectField);

/***/ },

/***/ "./assets/js/components/fields/TextField.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/fields/TextField.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const TextField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalInputControl, {
  __next40pxDefaultSize: true,
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  value: value ?? '',
  disabled: disabled,
  onChange: onChange,
  placeholder: field.placeholder || ''
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TextField);

/***/ },

/***/ "./assets/js/components/fields/ToggleField.jsx"
/*!*****************************************************!*\
  !*** ./assets/js/components/fields/ToggleField.jsx ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const ToggleField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  checked: !!value,
  disabled: disabled,
  onChange: onChange
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ToggleField);

/***/ },

/***/ "./assets/js/components/fields/TokenListField.jsx"
/*!********************************************************!*\
  !*** ./assets/js/components/fields/TokenListField.jsx ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const TokenListField = ({
  field,
  value,
  onChange,
  disabled
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
  __next40pxDefaultSize: true,
  label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
    label: field.label,
    tooltip: field.tooltip
  }) : field.label,
  placeholder: field.placeholder || '',
  value: Array.isArray(value) ? value : [],
  disabled: disabled,
  onChange: onChange,
  suggestions: []
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TokenListField);

/***/ },

/***/ "./assets/js/components/fields/UnitField.jsx"
/*!***************************************************!*\
  !*** ./assets/js/components/fields/UnitField.jsx ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



/**
 * Unit multipliers for converting to/from seconds.
 */

const UNIT_MULTIPLIERS = {
  s: 1,
  m: 60,
  h: 3600,
  d: 86400,
  w: 604800,
  M: 2592000
};

/**
 * Convert a value in seconds to the best display number and unit.
 *
 * @return {{ number: number, unit: string }}
 */
const secondsToDisplay = (seconds, units) => {
  const unitValues = units.map(u => u.value);

  // Try from largest unit to smallest to find the best fit.
  const sorted = [...unitValues].sort((a, b) => (UNIT_MULTIPLIERS[b] || 1) - (UNIT_MULTIPLIERS[a] || 1));
  for (const unit of sorted) {
    const multiplier = UNIT_MULTIPLIERS[unit] || 1;
    if (seconds % multiplier === 0) {
      return {
        number: seconds / multiplier,
        unit
      };
    }
  }
  return {
    number: seconds,
    unit: unitValues[0] || 's'
  };
};

/**
 * Convert a combined value string (e.g. "24h") back to seconds.
 */
const displayToSeconds = combinedValue => {
  const numValue = parseFloat(combinedValue);
  const unit = combinedValue.replace(numValue, '');
  const multiplier = UNIT_MULTIPLIERS[unit] || 1;
  return numValue * multiplier;
};
const UnitField = ({
  field,
  value,
  onChange,
  disabled
}) => {
  const units = field.units || [{
    value: 's',
    label: 'Seconds'
  }, {
    value: 'm',
    label: 'Minutes'
  }, {
    value: 'h',
    label: 'Hours'
  }, {
    value: 'd',
    label: 'Days'
  }];
  const storeAsSeconds = field.save === 'seconds';
  const display = storeAsSeconds ? secondsToDisplay(value || 0, units) : {
    number: value || 0,
    unit: units[0]?.value || 's'
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalUnitControl, {
    __next40pxDefaultSize: true,
    label: field.tooltip ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_1__.LabelWithTooltip, {
      label: field.label,
      tooltip: field.tooltip
    }) : field.label,
    disabled: disabled,
    value: `${display.number}${display.unit}`,
    unit: display.unit,
    onChange: combinedValue => {
      if (storeAsSeconds) {
        onChange(displayToSeconds(combinedValue));
      } else {
        onChange(parseFloat(combinedValue));
      }
    },
    min: field.min || 0,
    units: units
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UnitField);

/***/ },

/***/ "./assets/js/utils/evaluateCondition.js"
/*!**********************************************!*\
  !*** ./assets/js/utils/evaluateCondition.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   resolveDotPath: () => (/* binding */ resolveDotPath)
/* harmony export */ });
/**
 * Evaluate a show/hide condition tuple against current settings.
 *
 * Supports:
 * - 2-tuple: [field, value]  → equality (or glob match if value contains *)
 * - 3-tuple: [field, operator, value] → operator comparison
 *
 * Operators: =, !=, >, >=, <, <=
 * Glob: * in string values acts as a positional wildcard.
 *
 * @param {Array}  rule     The condition tuple.
 * @param {Object} settings Nested settings object.
 * @return {boolean}
 */
const OPERATORS = new Set(['!=', '>', '>=', '<', '<=']);
const resolveDotPath = (obj, path) => {
  const parts = path.split('.');
  let current = obj;
  for (const part of parts) {
    if (current == null || typeof current !== 'object') {
      return undefined;
    }
    current = current[part];
  }
  return current;
};
const globMatch = (value, pattern) => {
  if (typeof value !== 'string') {
    return false;
  }
  const segments = pattern.split('*');

  // Single * at start: endsWith check.
  if (segments.length === 2 && segments[0] === '') {
    return value.endsWith(segments[1]);
  }
  // Single * at end: startsWith check.
  if (segments.length === 2 && segments[1] === '') {
    return value.startsWith(segments[0]);
  }

  // General glob: each segment must appear in order.
  let pos = 0;
  for (let i = 0; i < segments.length; i++) {
    const seg = segments[i];
    if (i === 0) {
      // First segment must be at the start.
      if (!value.startsWith(seg)) {
        return false;
      }
      pos = seg.length;
    } else if (i === segments.length - 1) {
      // Last segment must be at the end.
      if (!value.endsWith(seg)) {
        return false;
      }
    } else {
      const idx = value.indexOf(seg, pos);
      if (idx === -1) {
        return false;
      }
      pos = idx + seg.length;
    }
  }
  return true;
};
const matchValue = (actual, expected) => {
  if (typeof expected === 'string' && expected.includes('*')) {
    return globMatch(actual, expected);
  }
  return actual === expected;
};
const evaluateCondition = (rule, settings) => {
  if (!Array.isArray(rule) || rule.length < 2) {
    return true;
  }
  let field, operator, expected;
  if (rule.length === 2) {
    [field, expected] = rule;
    operator = '=';
  } else {
    [field, operator, expected] = rule;
  }
  const actual = resolveDotPath(settings, field);
  switch (operator) {
    case '=':
      return matchValue(actual, expected);
    case '!=':
      return !matchValue(actual, expected);
    case '>':
      return Number(actual) > Number(expected);
    case '>=':
      return Number(actual) >= Number(expected);
    case '<':
      return Number(actual) < Number(expected);
    case '<=':
      return Number(actual) <= Number(expected);
    default:
      return true;
  }
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (evaluateCondition);

/***/ },

/***/ "./assets/css/millibase.scss"
/*!***********************************!*\
  !*** ./assets/css/millibase.scss ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/dom-ready"
/*!**********************************!*\
  !*** external ["wp","domReady"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["domReady"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "@wordpress/primitives"
/*!************************************!*\
  !*** external ["wp","primitives"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["primitives"];

/***/ },

/***/ "@wordpress/sanitize"
/*!**********************************!*\
  !*** external ["wp","sanitize"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["sanitize"];

/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/icon/index.mjs"
/*!*******************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/icon/index.mjs ***!
  \*******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
// packages/icons/src/icon/index.ts

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/backup.mjs"
/*!***********************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/backup.mjs ***!
  \***********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ backup_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/backup.tsx


var backup_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, { d: "M5.5 12h1.75l-2.5 3-2.5-3H4a8 8 0 113.134 6.35l.907-1.194A6.5 6.5 0 105.5 12zm9.53 1.97l-2.28-2.28V8.5a.75.75 0 00-1.5 0V12a.747.747 0 00.218.529l1.282-.84-1.28.842 2.5 2.5a.75.75 0 101.06-1.061z" }) });

//# sourceMappingURL=backup.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/caution.mjs"
/*!************************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/caution.mjs ***!
  \************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ caution_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/caution.tsx


var caution_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(
  _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path,
  {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M5.5 12a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0ZM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm-.75 12v-1.5h1.5V16h-1.5Zm0-8v5h1.5V8h-1.5Z"
  }
) });

//# sourceMappingURL=caution.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/flip-vertical.mjs"
/*!******************************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/flip-vertical.mjs ***!
  \******************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ flip_vertical_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/flip-vertical.tsx


var flip_vertical_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, { d: "M2 11.2v1.5h20v-1.5H2zM5.5 6c0-.3.2-.5.5-.5h12c.3 0 .5.2.5.5v3H20V6c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v3h1.5V6zm2 14h2v-1.5h-2V20zm3.5 0h2v-1.5h-2V20zm7-1.5V20c1.1 0 2-.9 2-2h-1.5c0 .3-.2.5-.5.5zm.5-2H20V15h-1.5v1.5zM5.5 18H4c0 1.1.9 2 2 2v-1.5c-.3 0-.5-.2-.5-.5zm0-3H4v1.5h1.5V15zm9 5h2v-1.5h-2V20z" }) });

//# sourceMappingURL=flip-vertical.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/help.mjs"
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/help.mjs ***!
  \*********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ help_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/help.tsx


var help_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, { d: "M12 4a8 8 0 1 1 .001 16.001A8 8 0 0 1 12 4Zm0 1.5a6.5 6.5 0 1 0-.001 13.001A6.5 6.5 0 0 0 12 5.5Zm.75 11h-1.5V15h1.5v1.5Zm-.445-9.234a3 3 0 0 1 .445 5.89V14h-1.5v-1.25c0-.57.452-.958.917-1.01A1.5 1.5 0 0 0 12 8.75a1.5 1.5 0 0 0-1.5 1.5H9a3 3 0 0 1 3.305-2.984Z" }) });

//# sourceMappingURL=help.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/lifesaver.mjs"
/*!**************************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/lifesaver.mjs ***!
  \**************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ lifesaver_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/lifesaver.tsx


var lifesaver_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(
  _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path,
  {
    fillRule: "evenodd",
    d: "M17.375 15.656A6.47 6.47 0 0018.5 12a6.47 6.47 0 00-.943-3.374l-1.262.813c.448.749.705 1.625.705 2.561a4.977 4.977 0 01-.887 2.844l1.262.813zm-1.951 1.87l-.813-1.261A4.976 4.976 0 0112 17c-.958 0-1.852-.27-2.613-.736l-.812 1.261A6.47 6.47 0 0012 18.5a6.47 6.47 0 003.424-.974zm-8.8-1.87A6.47 6.47 0 015.5 12c0-1.235.344-2.39.943-3.373l1.261.812A4.977 4.977 0 007 12c0 1.056.328 2.036.887 2.843l-1.262.813zm2.581-7.803A4.977 4.977 0 0112 7c1.035 0 1.996.314 2.794.853l.812-1.262A6.47 6.47 0 0012 5.5a6.47 6.47 0 00-3.607 1.092l.812 1.261zM12 20a8 8 0 100-16 8 8 0 000 16zm0-4.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z",
    clipRule: "evenodd"
  }
) });

//# sourceMappingURL=lifesaver.mjs.map


/***/ },

/***/ "./node_modules/@wordpress/icons/build-module/library/more-vertical.mjs"
/*!******************************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/more-vertical.mjs ***!
  \******************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ more_vertical_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
// packages/icons/src/library/more-vertical.tsx


var more_vertical_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, { d: "M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z" }) });

//# sourceMappingURL=more-vertical.mjs.map


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!********************************!*\
  !*** ./assets/js/millibase.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useSettings: () => (/* reexport safe */ _components_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_4__.useSettings),
/* harmony export */   useSnackbar: () => (/* reexport safe */ _components_SnackbarProvider_jsx__WEBPACK_IMPORTED_MODULE_3__.useSnackbar)
/* harmony export */ });
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/dom-ready */ "@wordpress/dom-ready");
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_SettingsApp_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/SettingsApp.jsx */ "./assets/js/components/SettingsApp.jsx");
/* harmony import */ var _components_SnackbarProvider_jsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/SnackbarProvider.jsx */ "./assets/js/components/SnackbarProvider.jsx");
/* harmony import */ var _components_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/SettingsProvider.jsx */ "./assets/js/components/SettingsProvider.jsx");
/* harmony import */ var _components_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/LabelWithTooltip.jsx */ "./assets/js/components/LabelWithTooltip.jsx");
/* harmony import */ var _css_millibase_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../css/millibase.scss */ "./assets/css/millibase.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);
/**
 * MilliBase — Entry point + global init function.
 *
 * This file is the webpack entry point. It:
 * 1. Defines the global MilliBase registry (init, registerComponent, registerFieldType)
 * 2. On DOM ready, auto-mounts a SettingsApp into each container with data-slug
 */









// ─── Global registry ────────────────────────────────────────────────

window.MilliBase = window.MilliBase || {};

// Configs store: { slug: configObject }
window.MilliBase.configs = window.MilliBase.configs || {};

// Custom components store: { name: Component }
window.MilliBase.customComponents = window.MilliBase.customComponents || {};

// Custom field types store: { type: Component }
window.MilliBase.customFieldTypes = window.MilliBase.customFieldTypes || {};

/**
 * Register a config for a slug. Called by wp_add_inline_script() from PHP.
 */
window.MilliBase.init = window.MilliBase.init || function (slug, config) {
  window.MilliBase.configs[slug] = config;
};

/**
 * Register a custom component (e.g., for custom tab content).
 *
 * @param {string}   name      The component name (referenced in schema as `component`).
 * @param {Function} component A React component or function component.
 */
window.MilliBase.registerComponent = function (name, component) {
  window.MilliBase.customComponents[name] = component;
};

/**
 * Register a custom field type.
 *
 * @param {string}   type      The field type string (used in schema `field.type`).
 * @param {Function} component A React component: receives { field, value, onChange, disabled }.
 */
window.MilliBase.registerFieldType = function (type, component) {
  window.MilliBase.customFieldTypes[type] = component;
};

// ─── Exposed components for custom tab authors ──────────────────────

window.MilliBase.components = {
  LabelWithTooltip: _components_LabelWithTooltip_jsx__WEBPACK_IMPORTED_MODULE_5__.LabelWithTooltip
};

// useSettings and useSnackbar are re-exported from the providers
// so custom components can import them:



// ─── Auto-mount ─────────────────────────────────────────────────────

_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default()(() => {
  // Find all containers with data-slug and mount a SettingsApp into each.
  const containers = document.querySelectorAll('[data-slug]');
  containers.forEach(container => {
    const slug = container.getAttribute('data-slug');
    const config = window.MilliBase.configs[slug];
    if (!config) {
      return;
    }
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createRoot)(container).render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_SnackbarProvider_jsx__WEBPACK_IMPORTED_MODULE_3__.SnackbarProvider, {
      slug: slug,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_SettingsProvider_jsx__WEBPACK_IMPORTED_MODULE_4__.SettingsProvider, {
        config: config,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_SettingsApp_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
          config: config
        })
      })
    }));
  });
});
})();

/******/ })()
;
//# sourceMappingURL=millibase.js.map