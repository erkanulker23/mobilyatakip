import{M as f,u as M}from"./Ch2Vc9wv.js";import{D as z,a as I,G as $,P as J,b as K,c as N,d as O,e as X,f as Y,W as Z}from"./Ch2Vc9wv.js";import{aB as T,r as C,ay as P,aC as w,aD as F,as as h,ap as S,aq as x,ar as G,ao as R,ax as b,a0 as D,C as E}from"./T_1NCrbE.js";import{FullScreenQuad as U}from"./DiXSYYBe.js";class B extends T{constructor(){super(),this.isEquirectCamera=!0}}class V extends C{constructor(...e){super(...e),this.iesMap=null,this.radius=0}copy(e,s){return super.copy(e,s),this.iesMap=e.iesMap,this.radius=e.radius,this}}class Q extends P{constructor(...e){super(...e),this.isCircular=!1}copy(e,s){return super.copy(e,s),this.isCircular=e.isCircular,this}}class q extends f{constructor(){super({uniforms:{envMap:{value:null},blur:{value:0}},vertexShader:`

				varying vec2 vUv;
				void main() {
					vUv = uv;
					gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );
				}

			`,fragmentShader:`

				#include <common>
				#include <cube_uv_reflection_fragment>

				${M}

				uniform sampler2D envMap;
				uniform float blur;
				varying vec2 vUv;
				void main() {

					vec3 rayDirection = equirectUvToDirection( vUv );
					gl_FragColor = textureCubeUV( envMap, rayDirection, blur );

				}

			`})}}class W{constructor(e){this.renderer=e,this.pmremGenerator=new w(e),this.copyQuad=new U(new q),this.renderTarget=new F(1,1,{type:S,format:h})}dispose(){this.pmremGenerator.dispose(),this.copyQuad.dispose(),this.renderTarget.dispose()}generate(e,s){const{pmremGenerator:m,renderTarget:l,copyQuad:c,renderer:r}=this,p=m.fromEquirectangular(e),{width:i,height:n}=e.image;l.setSize(i,n),c.material.envMap=p.texture,c.material.blur=s;const g=r.getRenderTarget(),v=r.autoClear;r.setRenderTarget(l),r.autoClear=!0,c.render(r),r.setRenderTarget(g),r.autoClear=v;const d=new Uint16Array(i*n*4),u=new Float32Array(i*n*4);r.readRenderTargetPixels(l,0,0,i,n,u);for(let o=0,y=u.length;o<y;o++)d[o]=x.toHalfFloat(u[o]);const a=new G(d,i,n,h,R);return a.minFilter=e.minFilter,a.magFilter=e.magFilter,a.wrapS=e.wrapS,a.wrapT=e.wrapT,a.mapping=b,a.needsUpdate=!0,p.dispose(),a}}class H extends D{constructor(e){super(e),this.isFogVolumeMaterial=!0,this.density=.015,this.emissive=new E,this.emissiveIntensity=0,this.opacity=.15,this.transparent=!0,this.roughness=1,this.metalness=0,this.setValues(e)}}export{W as BlurredEnvMapGenerator,z as DenoiseMaterial,I as DynamicPathTracingSceneGenerator,B as EquirectCamera,H as FogVolumeMaterial,$ as GradientEquirectTexture,J as PathTracingRenderer,K as PathTracingSceneGenerator,N as PathTracingSceneWorker,O as PhysicalCamera,X as PhysicalPathTracingMaterial,V as PhysicalSpotLight,Y as ProceduralEquirectTexture,Q as ShapedAreaLight,Z as WebGLPathTracer};
