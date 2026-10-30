document.addEventListener('DOMContentLoaded', function () {
  setUpStages();
  setUpPicker();
  setUpFilterForm();
  setUpQuantity();
  setUpContactForm();
});

// product tilt feature for the home page feature area and the product page
function setUpStages() {
  // does not override "reduce motion" setting on the user device
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var stages = document.querySelectorAll('.stage');
  for (var i = 0; i < stages.length; i++) {
    initStage(stages[i]);
  }
}

function initStage(stage) {
  var area = stage.parentElement;

  area.addEventListener('mouseenter', function () {
    stage.classList.add('is-live');
  });

  area.addEventListener('mousemove', function (e) {
    // tracking mouse position for tilt
    var box = area.getBoundingClientRect();
    var x = (e.clientX - box.left) / box.width - 0.5;
    var y = (e.clientY - box.top) / box.height - 0.5;

    stage.style.setProperty('--rx', (-y * 14).toFixed(2) + 'deg');   // tilt up/down
    stage.style.setProperty('--ry', (x * 20).toFixed(2) + 'deg');    // tilt left/right
  });

  area.addEventListener('mouseleave', function () {
    stage.classList.remove('is-live');
    stage.style.removeProperty('--rx');
    stage.style.removeProperty('--ry');
  });
}

// picking the product in main feature area without reloading
function setUpPicker() {
  var links  = document.querySelectorAll('.picker__item');
  var slides = document.querySelectorAll('.slide');
  if (!links.length || !slides.length) return;

  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function (e) {
      e.preventDefault();
      var id = this.getAttribute('data-pick');

      for (var s = 0; s < slides.length; s++) {
        var show = slides[s].getAttribute('data-slide') === id;
        slides[s].hidden = !show;
        if (show) {
          var img = slides[s].querySelector('.stage__img');
          if (img) img.loading = 'eager';
        }
      }
      for (var l = 0; l < links.length; l++) {
        if (links[l] === this) {
          links[l].setAttribute('aria-current', 'true');
        } else {
          links[l].removeAttribute('aria-current');
        }
      }
      // refresh does not change the current product on feature
      if (window.history && history.replaceState) {
        history.replaceState(null, '', 'index.php?pick=' + id);
      }
    });
  }
}

// respond to search box and filter change
function setUpFilterForm() {
  var form = document.getElementById('filter-form');
  if (!form) return;

  var search = document.getElementById('f-q');
  var error  = document.getElementById('f-q-error');

  form.addEventListener('submit', function (event) {
    var value = search.value;

    // catching errors in search bar (no spaces only, cannot be too long)
    if (value.length > 0 && value.trim() === '') {
      event.preventDefault();
      showError(search, error, 'Type at least one letter or number, or leave the search empty.');
      return;
    }
    if (value.trim().length > 50) {
      event.preventDefault();
      showError(search, error, 'Keep your search under 50 characters.');
      return;
    }
    clearError(search, error);
  });

  search.addEventListener('input', function () { clearError(search, error); });

  // respond to radio button immediately
  var autoInputs = form.querySelectorAll('input[type="radio"], input[type="checkbox"], select');
  for (var i = 0; i < autoInputs.length; i++) {
    autoInputs[i].addEventListener('change', function () {
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }
}

// selecting quantity before add to cart
function setUpQuantity() {
  var box = document.querySelector('.qty');
  var form = document.getElementById('buy-form');
  if (!box || !form) return;

  var input = box.querySelector('input');
  var error = document.getElementById('qty-error');
  var max = parseInt(box.getAttribute('data-max'), 10) || 0;

  var buttons = box.querySelectorAll('button[data-step]');
  for (var i = 0; i < buttons.length; i++) {
    buttons[i].addEventListener('click', function () {
      var step = parseInt(this.getAttribute('data-step'), 10);
      var next = (parseInt(input.value, 10) || 1) + step;
      input.value = Math.min(Math.max(next, 1), Math.max(max, 1));
      clearError(input, error);
    });
  }

  form.addEventListener('submit', function (event) {
    var raw = input.value.trim();
    var qty = Number(raw);

    if (!/^\d+$/.test(raw) || qty < 1) {
      event.preventDefault();
      showError(input, error, 'Enter a whole number of 1 or more.');
    } else if (qty > max) {
      event.preventDefault();
      showError(input, error, 'Only ' + max + ' can be added to one order.');
    }
  });

  input.addEventListener('input', function () { clearError(input, error); });
}

// contact form validation
function setUpContactForm() {
  var form = document.getElementById('contact-form');
  if (!form) return;

  var name    = document.getElementById('c-name');
  var email   = document.getElementById('c-email');
  var topic   = document.getElementById('c-topic');
  var order   = document.getElementById('c-order');
  var message = document.getElementById('c-message');
  var count   = document.getElementById('c-count');

  var fields = [
    [name,    document.getElementById('c-name-error')],
    [email,   document.getElementById('c-email-error')],
    [topic,   document.getElementById('c-topic-error')],
    [order,   document.getElementById('c-order-error')],
    [message, document.getElementById('c-message-error')]
  ];

  function check(input) {
    var value = input.value.trim();

    if (input === name) {
      if (value === '') return 'Enter your name.';
      if (value.length < 2) return 'Your name should be 2 to 100 characters.';
    }
    if (input === email) {
      if (value === '') return 'Enter your email address.';
      // something@something.something
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Enter an email address like name@example.com.';
    }
    if (input === topic && value === '') {
      return 'Choose what your message is about.';
    }
    if (input === order) {
      if (value !== '' && !/^\d{4,7}$/.test(value)) return 'Order numbers are 4 to 7 digits, e.g. 1004.';
      if (value === '' && topic.value === 'order') return 'Enter your order number so we can find your order.';
    }
    if (input === message) {
      if (value.length < 10) return 'Write at least 10 characters so we know how to help.';
      if (value.length > 1000) return 'Keep your message under 1000 characters.';
    }
    return '';
  }

  form.addEventListener('submit', function (event) {
    var firstProblem = null;

    for (var i = 0; i < fields.length; i++) {
      var problem = check(fields[i][0]);
      if (problem) {
        showError(fields[i][0], fields[i][1], problem);
        if (!firstProblem) firstProblem = fields[i][0];
      } else {
        clearError(fields[i][0], fields[i][1]);
      }
    }

    if (firstProblem) {
      event.preventDefault();
      firstProblem.focus();
    }
  });

  // respond to error after change
  for (var i = 0; i < fields.length; i++) {
    (function (input, error) {
      input.addEventListener('input', function () { clearError(input, error); });
    })(fields[i][0], fields[i][1]);
  }

  // live character count under the message box
  message.addEventListener('input', function () {
    count.textContent = message.value.length;
  });
}

// handles error messages
function showError(input, el, message) {
  el.textContent = message;
  el.hidden = false;
  input.setAttribute('aria-invalid', 'true');
  input.setAttribute('aria-describedby', el.id);
  input.focus();
}

function clearError(input, el) {
  el.hidden = true;
  el.textContent = '';
  input.removeAttribute('aria-invalid');
}
