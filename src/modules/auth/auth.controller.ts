import { Controller, Post, Body, Get, Put, Param, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { AuthService } from './auth.service';
import { LoginDto } from './dto/login.dto';
import { RegisterDto } from './dto/register.dto';
import { CurrentUser } from './decorators/current-user.decorator';
import { User } from '../../entities/user.entity';
import { Roles } from './decorators/roles.decorator';
import { RolesGuard } from './guards/roles.guard';
import { UserRole } from '../../common/enums/role.enum';

@Controller('auth')
export class AuthController {
  constructor(private auth: AuthService) {}

  @Post('login')
  login(@Body() dto: LoginDto) {
    return this.auth.login(dto);
  }

  @Post('register')
  register(@Body() dto: RegisterDto) {
    return this.auth.register(dto);
  }

  @Get('me')
  @UseGuards(AuthGuard('jwt'))
  me(@CurrentUser() user: User) {
    return this.auth.getProfile(user.id);
  }

  @Get('users')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  listUsers() {
    return this.auth.findAll();
  }

  @Get('assignable-users')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN, UserRole.SSH, UserRole.SATIS)
  assignableUsers() {
    return this.auth.findAssignable();
  }

  @Post('users')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  createUser(@Body() body: { email: string; name: string; password: string; role?: UserRole }) {
    return this.auth.register(body as import('./dto/register.dto').RegisterDto);
  }

  @Put('users/:id/role')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  updateUserRole(@Param('id') id: string, @Body() body: { role: UserRole }) {
    return this.auth.updateRole(id, body.role);
  }
}
