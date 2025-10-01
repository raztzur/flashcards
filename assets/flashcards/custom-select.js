/**
 * Custom Select Component - הופך select רגיל לdropdown מותאם אישית
 */

class CustomSelect {
  constructor(selectElement) {
    this.select = selectElement;
    this.isOpen = false;
    this.selectedValue = this.select.value;
    this.selectedText = this.getSelectedText();
    
    this.init();
  }
  
  init() {
    // יצירת המבנה המותאם אישית
    this.createCustomStructure();
    
    // הסתרת ה-select המקורי
    this.select.style.display = 'none';
    
    // הוספת event listeners
    this.bindEvents();
    
    // עדכון הערך הראשוני
    this.updateSelected();
  }
  
  createCustomStructure() {
    // יצירת container ראשי
    this.container = document.createElement('div');
    this.container.className = 'custom-select';
    
    // יצירת הtrigger (הכפתור שמקבל את הקליק)
    this.trigger = document.createElement('div');
    this.trigger.className = 'custom-select-trigger';
    this.trigger.innerHTML = `<span class="custom-select-text">${this.selectedText}</span>`;
    
    // יצירת הdropdown
    this.dropdown = document.createElement('div');
    this.dropdown.className = 'custom-select-dropdown';
    
    // יצירת אפשרויות
    this.createOptions();
    
    // הרכבת המבנה
    this.container.appendChild(this.trigger);
    this.container.appendChild(this.dropdown);
    
    // הוספה לDOM אחרי ה-select המקורי
    this.select.parentNode.insertBefore(this.container, this.select.nextSibling);
  }
  
  createOptions() {
    const options = Array.from(this.select.options);
    this.dropdown.innerHTML = '';
    
    options.forEach((option, index) => {
      const optionElement = document.createElement('div');
      optionElement.className = 'custom-select-option';
      optionElement.textContent = option.textContent;
      optionElement.dataset.value = option.value;
      optionElement.dataset.index = index;
      
      if (option.disabled) {
        optionElement.classList.add('disabled');
      }
      
      if (option.value === this.selectedValue) {
        optionElement.classList.add('selected');
      }
      
      this.dropdown.appendChild(optionElement);
    });
  }
  
  bindEvents() {
    // פתיחה/סגירה של הdropdown
    this.trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      this.toggle();
    });
    
    // בחירת אפשרות
    this.dropdown.addEventListener('click', (e) => {
      const option = e.target.closest('.custom-select-option');
      if (option && !option.classList.contains('disabled')) {
        this.selectOption(option);
      }
    });
    
    // סגירה בלחיצה מחוץ לרכיב
    document.addEventListener('click', (e) => {
      if (!this.container.contains(e.target)) {
        this.close();
      }
    });
    
    // תמיכה במקלדת
    this.container.addEventListener('keydown', (e) => {
      this.handleKeyboard(e);
    });
    
    // הפיכת הcontainer לfocusable
    this.container.setAttribute('tabindex', '0');
  }
  
  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }
  
  open() {
    this.isOpen = true;
    this.container.classList.add('open');
    this.container.focus();
  }
  
  close() {
    this.isOpen = false;
    this.container.classList.remove('open');
  }
  
  selectOption(optionElement) {
    const value = optionElement.dataset.value;
    const text = optionElement.textContent;
    const index = optionElement.dataset.index;
    
    // עדכון הselect המקורי
    this.select.selectedIndex = index;
    this.select.value = value;
    
    // עדכון המשתנים הפנימיים
    this.selectedValue = value;
    this.selectedText = text;
    
    // עדכון התצוגה
    this.updateSelected();
    
    // הפעלת event change על ה-select המקורי
    this.select.dispatchEvent(new Event('change', { bubbles: true }));
    
    // סגירת הdropdown
    this.close();
  }
  
  updateSelected() {
    // עדכון הטקסט ב-trigger
    const textElement = this.trigger.querySelector('.custom-select-text');
    textElement.textContent = this.selectedText;
    
    // עדכון הclass selected באפשרויות
    this.dropdown.querySelectorAll('.custom-select-option').forEach(option => {
      option.classList.remove('selected');
      if (option.dataset.value === this.selectedValue) {
        option.classList.add('selected');
      }
    });
  }
  
  getSelectedText() {
    const selectedOption = this.select.options[this.select.selectedIndex];
    return selectedOption ? selectedOption.textContent : '';
  }
  
  handleKeyboard(e) {
    if (!this.isOpen) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        this.open();
      }
      return;
    }
    
    const options = Array.from(this.dropdown.querySelectorAll('.custom-select-option:not(.disabled)'));
    const currentIndex = options.findIndex(opt => opt.classList.contains('selected'));
    
    switch (e.key) {
      case 'Escape':
        e.preventDefault();
        this.close();
        break;
        
      case 'Enter':
        e.preventDefault();
        if (currentIndex >= 0) {
          this.selectOption(options[currentIndex]);
        }
        break;
        
      case 'ArrowDown':
        e.preventDefault();
        const nextIndex = Math.min(currentIndex + 1, options.length - 1);
        this.highlightOption(options[nextIndex]);
        break;
        
      case 'ArrowUp':
        e.preventDefault();
        const prevIndex = Math.max(currentIndex - 1, 0);
        this.highlightOption(options[prevIndex]);
        break;
    }
  }
  
  highlightOption(optionElement) {
    this.dropdown.querySelectorAll('.custom-select-option').forEach(opt => {
      opt.classList.remove('selected');
    });
    optionElement.classList.add('selected');
  }
  
  // פונקציה להרס הרכיב
  destroy() {
    this.container.remove();
    this.select.style.display = '';
  }
}

// פונקציה להפעלה אוטומטית על כל ה-select elements
function initCustomSelects() {
  const selects = document.querySelectorAll('select:not(.no-custom)');
  selects.forEach(select => {
    if (!select.dataset.customized) {
      new CustomSelect(select);
      select.dataset.customized = 'true';
    }
  });
}

// הפעלה אוטומטית כשהדף נטען
document.addEventListener('DOMContentLoaded', initCustomSelects);

// הפעלה גם על תוכן שנוסף דינמית
document.addEventListener('selectsAdded', initCustomSelects);