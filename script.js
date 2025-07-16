document.addEventListener("DOMContentLoaded", () => {

  const hamburger = document.querySelector(".hamburger-menu")
  const menu = document.querySelector(".menu")

  if (hamburger) {
    hamburger.addEventListener("click", function () {
      this.classList.toggle("active")
      menu.classList.toggle("active")

    
      const bars = this.querySelectorAll(".bar")
      if (this.classList.contains("active")) {
        bars[0].style.transform = "rotate(45deg) translate(5px, 5px)"
        bars[1].style.opacity = "0"
        bars[2].style.transform = "rotate(-45deg) translate(5px, -5px)"
      } else {
        bars[0].style.transform = "none"
        bars[1].style.opacity = "1"
        bars[2].style.transform = "none"
      }
    })
  }


  function changeImage(thumbnail) {
    const mainImage = document.getElementById("main-product-image")
    if (mainImage) {
      mainImage.src = thumbnail.src

      
      const thumbnails = document.querySelectorAll(".thumbnail")
      thumbnails.forEach((thumb) => {
        thumb.classList.remove("active")
      })
      thumbnail.classList.add("active")
    }
  }

  
  window.changeImage = changeImage


  const tabButtons = document.querySelectorAll(".tab-btn")

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const tabId = this.getAttribute("data-tab")

    
      const tabPanels = document.querySelectorAll(".tab-panel")
      tabPanels.forEach((panel) => {
        panel.classList.remove("active")
      })

    
      tabButtons.forEach((btn) => {
        btn.classList.remove("active")
      })

 
      this.classList.add("active")
      document.getElementById(tabId).classList.add("active")
    })
  })


  const authTabs = document.querySelectorAll(".auth-tab")

  authTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      const tabId = this.getAttribute("data-tab")

  
      const authForms = document.querySelectorAll(".auth-form")
      authForms.forEach((form) => {
        form.classList.remove("active")
      })


      authTabs.forEach((t) => {
        t.classList.remove("active")
      })

   
      this.classList.add("active")
      document.getElementById(tabId).classList.add("active")
    })
  })


  const togglePasswordButtons = document.querySelectorAll(".toggle-password")

  togglePasswordButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const passwordInput = this.previousElementSibling
      const icon = this.querySelector("i")

      if (passwordInput.type === "password") {
        passwordInput.type = "text"
        icon.classList.remove("fa-eye")
        icon.classList.add("fa-eye-slash")
      } else {
        passwordInput.type = "password"
        icon.classList.remove("fa-eye-slash")
        icon.classList.add("fa-eye")
      }
    })
  })


  const registerPassword = document.getElementById("register-password")

  if (registerPassword) {
    registerPassword.addEventListener("input", function () {
      const password = this.value
      const strengthBar = document.querySelector(".strength-fill")
      const strengthText = document.querySelector(".strength-text")

      if (strengthBar && strengthText) {
        let strength = 0

        if (password.length >= 8) strength += 25
        if (password.match(/[a-z]/)) strength += 25
        if (password.match(/[A-Z]/)) strength += 25
        if (password.match(/[0-9]/)) strength += 25

        strengthBar.style.width = strength + "%"

        if (strength < 25) {
          strengthBar.style.backgroundColor = "#d9534f"
          strengthText.textContent = "Mot de passe très faible"
        } else if (strength < 50) {
          strengthBar.style.backgroundColor = "#f0ad4e"
          strengthText.textContent = "Mot de passe faible"
        } else if (strength < 75) {
          strengthBar.style.backgroundColor = "#5bc0de"
          strengthText.textContent = "Mot de passe moyen"
        } else {
          strengthBar.style.backgroundColor = "#5cb85c"
          strengthText.textContent = "Mot de passe fort"
        }
      }
    })
  }

  
  const searchIcon = document.querySelector(".search-icon")
  const searchOverlay = document.createElement("div")
  searchOverlay.className = "search-overlay"
  searchOverlay.innerHTML = `
    <button class="search-close"><i class="fas fa-times"></i></button>
    <div class="search-container">
      <form class="search-form">
        <input type="text" class="search-input" placeholder="Rechercher un produit...">
        <button type="submit" class="search-submit"><i class="fas fa-search"></i></button>
      </form>
      <div class="search-results"></div>
    </div>
  `
  document.body.appendChild(searchOverlay)

  if (searchIcon) {
    searchIcon.addEventListener("click", (e) => {
      e.preventDefault()
      searchOverlay.classList.add("active")
      document.querySelector(".search-input").focus()
    })
  }

  const searchClose = searchOverlay.querySelector(".search-close")
  if (searchClose) {
    searchClose.addEventListener("click", () => {
      searchOverlay.classList.remove("active")
    })
  }

  const searchForm = searchOverlay.querySelector(".search-form")
  if (searchForm) {
    searchForm.addEventListener("submit", (e) => {
      e.preventDefault()
      const searchTerm = searchOverlay.querySelector(".search-input").value.toLowerCase()

      if (searchTerm.length < 2) {
        showNotification("Veuillez entrer au moins 2 caractères", "info")
        return
      }

      
      const searchResults = [
        {
          name: "Éclair Chocolat Grand Cru",
          description: "Pâte à choux croustillante, ganache onctueuse au chocolat 70%",
          price: "8,50 €",
          image: "/placeholder.svg?height=60&width=60",
        },
        {
          name: "Tarte Framboise Pistache",
          description: "Pâte sablée, crème de pistache, framboises fraîches",
          price: "7,90 €",
          image: "/placeholder.svg?height=60&width=60",
        },
        {
          name: "Macaron Caramel Beurre Salé",
          description: "Coques craquantes, ganache onctueuse au caramel",
          price: "2,50 €",
          image: "/placeholder.svg?height=60&width=60",
        },
      ].filter(
        (product) =>
          product.name.toLowerCase().includes(searchTerm) || product.description.toLowerCase().includes(searchTerm),
      )

      const searchResultsContainer = searchOverlay.querySelector(".search-results")
      searchResultsContainer.innerHTML = ""
      searchResultsContainer.style.display = "block"

      if (searchResults.length > 0) {
        searchResults.forEach((product) => {
          const resultItem = document.createElement("a")
          resultItem.href = "produit.html"
          resultItem.className = "search-result-item"
          resultItem.innerHTML = `
            <div class="search-result-image">
              <img src="${product.image}" alt="${product.name}">
            </div>
            <div class="search-result-details">
              <h3>${product.name}</h3>
              <p>${product.description}</p>
            </div>
            <div class="search-result-price">${product.price}</div>
          `
          searchResultsContainer.appendChild(resultItem)
        })
      } else {
        searchResultsContainer.innerHTML = `
          <div class="search-no-results">
            <p>Aucun résultat trouvé pour "${searchTerm}"</p>
          </div>
        `
      }
    })
  }

  const shippingForm = document.getElementById("shipping-form")
  if (shippingForm) {
    shippingForm.addEventListener("submit", (e) => {
      e.preventDefault()
      window.location.href = "paiement.html"
    })
  }


  function showNotification(message, type) {
    const notification = document.createElement("div")
    notification.className = `notification ${type}`
    notification.innerHTML = `
      <div class="notification-content">
        <i class="fas ${type === "success" ? "fa-check-circle" : "fa-info-circle"}"></i>
        <span>${message}</span>
      </div>
      <button class="notification-close"><i class="fas fa-times"></i></button>
    `

    document.body.appendChild(notification)

   
    setTimeout(() => {
      notification.classList.add("show")
    }, 10)

    
    setTimeout(() => {
      notification.classList.remove("show")
      setTimeout(() => {
        notification.remove()
      }, 300)
    }, 3000)

    const closeButton = notification.querySelector(".notification-close")
    closeButton.addEventListener("click", () => {
      notification.classList.remove("show")
      setTimeout(() => {
        notification.remove()
      }, 300)
    })
  }

 
  const notificationStyles = document.createElement("style")
  notificationStyles.textContent = `
    .notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: white;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-md);
      padding: 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s ease;
      z-index: 1000;
      min-width: 300px;
    }
    
    .notification.show {
      transform: translateY(0);
      opacity: 1;
    }
    
    .notification-content {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .notification.success .fa-check-circle {
      color: var(--success-color);
    }
    
    .notification.info .fa-info-circle {
      color: var(--primary-color);
    }
    
    .notification-close {
      background: none;
      border: none;
      color: var(--text-lighter);
      cursor: pointer;
    }
  `
  document.head.appendChild(notificationStyles)


  const faqItems = document.querySelectorAll(".faq-item")

  if (faqItems.length > 0) {
    faqItems.forEach((item) => {
      const question = item.querySelector(".faq-question")
      const answer = item.querySelector(".faq-answer")
      const toggle = item.querySelector(".faq-toggle")

      question.addEventListener("click", () => {
        const isActive = item.classList.contains("active")

   
        faqItems.forEach((faq) => {
          faq.classList.remove("active")
          faq.querySelector(".faq-answer").style.display = "none"
          faq.querySelector(".faq-toggle i").className = "fas fa-plus"
        })

      
        if (!isActive) {
          item.classList.add("active")
          answer.style.display = "block"
          toggle.querySelector("i").className = "fas fa-minus"
        }
      })
    })
  }

 
  if (window.location.pathname.includes("admin.html")) {
    const adminLoggedIn = sessionStorage.getItem("adminLoggedIn")
    if (!adminLoggedIn) {
      window.location.href = "admin-login.html"
    }
  }


  const instagramPosts = document.querySelectorAll(".instagram-post")

  instagramPosts.forEach((post) => {

    const heartIcon = post.querySelector(".post-icons .fa-heart")
    const likesElement = post.querySelector(".post-likes")

    if (heartIcon && likesElement) {
      heartIcon.addEventListener("click", function () {
        const isLiked = this.classList.contains("fas")
        const likesText = likesElement.textContent
        const likesCount = Number.parseInt(likesText.match(/\d+/)[0])

        if (isLiked) {
 
          this.classList.remove("fas")
          this.classList.add("far")
          this.style.color = "#262626"
          likesElement.textContent = `${likesCount - 1} J'aime`
        } else {
         
          this.classList.remove("far")
          this.classList.add("fas")
          this.style.color = "#ed4956"
          likesElement.textContent = `${likesCount + 1} J'aime`

      
          this.classList.add("like-animation")
          setTimeout(() => {
            this.classList.remove("like-animation")
          }, 500)
        }
      })
    }

 
    const bookmarkIcon = post.querySelector(".post-bookmark .fa-bookmark")

    if (bookmarkIcon) {
      bookmarkIcon.addEventListener("click", function () {
        const isSaved = this.classList.contains("fas")

        if (isSaved) {
          
          this.classList.remove("fas")
          this.classList.add("far")
        } else {
       
          this.classList.remove("far")
          this.classList.add("fas")

         
          showNotification("Post enregistré dans vos favoris", "success")
        }
      })
    }

 
    const postImage = post.querySelector(".post-image")

    if (postImage && heartIcon) {
      postImage.addEventListener("dblclick", function () {
        if (!heartIcon.classList.contains("fas")) {
       
          heartIcon.classList.remove("far")
          heartIcon.classList.add("fas")
          heartIcon.style.color = "#ed4956"

          const likesText = likesElement.textContent
          const likesCount = Number.parseInt(likesText.match(/\d+/)[0])
          likesElement.textContent = `${likesCount + 1} J'aime`

      
          const heart = document.createElement("div")
          heart.className = "heart-animation"
          heart.innerHTML = '<i class="fas fa-heart"></i>'
          this.appendChild(heart)

          setTimeout(() => {
            heart.remove()
          }, 1000)
        }
      })
    }
  })

 
  const likeAnimationStyles = document.createElement("style")
  likeAnimationStyles.textContent = `
    .like-animation {
      animation: likeAnimation 0.5s ease;
    }
    
    @keyframes likeAnimation {
      0% { transform: scale(1); }
      50% { transform: scale(1.3); }
      100% { transform: scale(1); }
    }
    
    .heart-animation {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 5rem;
      opacity: 0;
      animation: heartAnimation 1s ease;
      pointer-events: none;
    }
    
    @keyframes heartAnimation {
      0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
      15% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
      30% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
      100% { opacity: 0; transform: translate(-50%, -50%) scale(1.5); }
    }
  `
  document.head.appendChild(likeAnimationStyles)
})

document.addEventListener("DOMContentLoaded", () => {
  
  const calendarItems = document.querySelectorAll(".calendar-item")

  calendarItems.forEach((item) => {
    item.addEventListener("click", function () {
      if (this.classList.contains("active")) {
        this.classList.remove("active")
      } else {
      
        calendarItems.forEach((el) => el.classList.remove("active"))
        this.classList.add("active")
      }
    })
  })
})

document.addEventListener("DOMContentLoaded", () => {
  const slides = document.querySelectorAll(".slide")
  const indicators = document.querySelectorAll(".indicator")
  let currentSlide = 0
  let slideInterval


  function showSlide(index) {
    slides.forEach((slide) => {
      slide.classList.remove("active")
    })

    indicators.forEach((indicator) => {
      indicator.classList.remove("active")
    })


    slides[index].classList.add("active")
    indicators[index].classList.add("active")

  
    currentSlide = index
  }

  
  function nextSlide() {
    let nextIndex = currentSlide + 1
    if (nextIndex >= slides.length) {
      nextIndex = 0
    }
    showSlide(nextIndex)
  }


  function startSlideInterval() {
    slideInterval = setInterval(nextSlide, 3000) 
  }

 
  function stopSlideInterval() {
    clearInterval(slideInterval)
  }

  
  indicators.forEach((indicator, index) => {
    indicator.addEventListener("click", () => {
      showSlide(index)
      stopSlideInterval()
      startSlideInterval()
    })
  })


  const slideshowContainer = document.querySelector(".slideshow-container")
  if (slideshowContainer) {
    slideshowContainer.addEventListener("mouseenter", stopSlideInterval)
    slideshowContainer.addEventListener("mouseleave", startSlideInterval)
  }

  startSlideInterval()


  function preloadImages() {
    slides.forEach((slide) => {
      const bgImage = slide.style.backgroundImage.match(/url$$['"]?([^'"]+)['"]?$$/)
      if (bgImage && bgImage[1]) {
        const img = new Image()
        img.src = bgImage[1]
      }
    })
  }

  preloadImages()
})

function getUrlParameter(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

  const product = products[productId];
  

  if (!product) return;
  
  
  document.title = `${product.name} | Délices Sucrés`;
  
  
  const breadcrumbSpan = document.querySelector('.breadcrumb span');
  if (breadcrumbSpan) {
    breadcrumbSpan.textContent = product.name;
  }
  
  const categoryElement = document.querySelector('.product-category');
  if (categoryElement) {
    categoryElement.textContent = product.category;
  }
  
  const titleElement = document.querySelector('.product-title');
  if (titleElement) {
    titleElement.textContent = product.name;
  }
  
  
  const priceElement = document.querySelector('.product-price');
  if (priceElement) {
    priceElement.textContent = product.price;
  }
  
 
  const mainImage = document.getElementById('main-product-image');
  const thumbnails = document.querySelectorAll('.thumbnail');
  
  if (mainImage) {
    mainImage.src = product.image;
    mainImage.alt = product.name;
  }
  
  if (thumbnails.length > 0) {
    thumbnails.forEach(thumbnail => {
      thumbnail.src = product.image;
      thumbnail.alt = product.name;
    });
  }
  
 
  const descriptionElement = document.querySelector('.product-description-long');
  if (descriptionElement) {
    descriptionElement.innerHTML = `<p>${product.description}</p>`;
  }
  
  
  const ingredientsElement = document.querySelector('.attribute-value');
  if (ingredientsElement) {
    ingredientsElement.textContent = product.ingredients;
  }
  
  
  const allergensElement = document.querySelectorAll('.attribute-value')[1];
  if (allergensElement) {
    allergensElement.textContent = product.allergens;
  }
  
  
  const ratingStars = document.querySelectorAll('.product-rating .stars i');
  const ratingCount = document.querySelector('.rating-count');
  
  if (product.rating && ratingStars.length > 0) {
   
    ratingStars.forEach(star => {
      star.className = 'far fa-star';
    });
    
    
    const fullStars = Math.floor(product.rating);
    const hasHalfStar = product.rating % 1 >= 0.5;
    
    
    for (let i = 0; i < fullStars; i++) {
      ratingStars[i].className = 'fas fa-star';
    }
    
    if (hasHalfStar && fullStars < 5) {
      ratingStars[fullStars].className = 'fas fa-star-half-alt';
    }
  }
  
  if (ratingCount && product.reviewCount) {
    ratingCount.textContent = `${product.reviewCount} avis`;
  }


document.addEventListener('DOMContentLoaded', loadProductDetails);