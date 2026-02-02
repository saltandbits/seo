panel.plugin('saltandbits/seo', {
  sections:{
    seo:{
      props:{
        content: Object,
      },
      data: function(){
        return {
          isSite: false,
          isHome: false,
          siteTitle: null,
          siteDescription: null,
          siteImage: null,
          siteUrl: null,
          pageTitle: null,
          pageUrl: null,
        }
      },
      created: async function(){
        const response = await this.load();
        this.isSite = response.isSite;
        this.isHome = response.isHome;
        this.siteTitle = response.siteTitle;
        this.siteDescription = response.siteDescription;
        this.siteImage = response.siteImage;
        this.siteUrl = response.siteUrl;
        this.pageTitle = response.pageTitle;
        this.pageUrl = response.pageUrl;
      },
      computed:{
        seoTitle(){
          return this.content.seotitle
        },
        seoDescription(){
          return this.content.seodescription
        },
        seoImage(){
          return this.content.seoimage?.[0]?.url || null;
        }
      },
      template: `
        <div class="k-section k-seo-preview">
          <!-- GOOGLE PREVIEW -->
          <div class="k-field-header k-seo-preview__label k-label k-field-label">
            <k-icon type="google" />
            <span class="k-label-text">Google preview</span>
          </div>
          <div class="k-google-search-preview">
            <div class="k-google-search-preview__header">
              <img class="k-google-search-preview__favicon" src="/assets/images/favicons/favicon.png" />
              <div class="k-google-search-preview__site-info">
                <span class="k-google-search-preview__site-title">{{ siteTitle }}</span>
                <span class="k-google-search-preview__url">{{ pageUrl }}</span>
              </div>
            </div>
            <div v-if="isSite">
              <h3 class="k-google-search-preview__title">{{ siteTitle }}</h3>
            </div>
            <div v-else>
              <h3 v-if="seoTitle" class="k-google-search-preview__title">{{ seoTitle }}</h3>
              <h3 class="k-google-search-preview__title" v-else><div v-if="isHome">{{ siteTitle }}</div><div v-else>{{ pageTitle }} | {{ siteTitle }}</div></h3>
            </div>
            <p v-if="seoDescription" class="k-google-search-preview__description">{{ seoDescription }}</p>
            <p class="k-google-search-preview__description" v-else>{{ siteDescription }}</p>
          </div>
          <!-- META PREVIEW -->
          <div class="k-field-header k-seo-preview__label k-label k-field-label">
            <k-icon type="facebook" />
            <span class="k-label-text">Meta preview</span>
          </div>
          <div class="k-facebook-preview">
            <div v-if="seoImage" class="k-facebook-preview__image">
              <img :src="seoImage" class="k-facebook-preview__img" />
            </div>
            <div v-else>
              <img :src="siteImage" class="k-facebook-preview__img" />
            </div>
            <div class="k-facebook-preview__content">
              <span class="k-facebook-preview__url">{{ siteUrl }}</span>
              <span class="k-facebook-preview__title">{{ siteTitle }}</span>
              <p v-if="seoDescription" class="k-facebook-preview__description">{{ seoDescription }}</p>
              <p class="k-facebook-preview__description" v-else>{{ siteDescription }}</p>
            </div>
          </div>
        </div>
      `
    }
  }
});